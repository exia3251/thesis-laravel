<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\ActivityLog;
use App\Models\PaymentRequest;
use App\Mail\OrderDelivered;
use App\Mail\PaymentReviewed;
use App\Services\Notifier;
use App\Services\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SalesController extends Controller
{
    // Display sales page
    public function index()
    {
        return view('admin.sales');
    }

    /**
     * Get sales, a page at a time.
     *
     * Searching and filtering happen here rather than in the browser. With a
     * year of trade the table is far too long to hand over whole, and once it
     * is paged a filter applied in the browser would only ever search the page
     * already on screen.
     */
    public function getSales(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'focus' => ['nullable', Rule::in(array_keys(self::FOCUS_SETS))],
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $query = Sale::with(['items.product', 'user', 'paymentRequests.user', 'paymentRequests.reviewer']);

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('sale_date', [$request->from, $request->to]);
        }

        $this->applyFocus($query, $request->input('focus'));

        if ($request->filled('search')) {
            $term = trim($request->search);

            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('receipt_no', 'like', "%{$term}%")
                    ->orWhere('sale_id', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', "%{$term}%"));
            });
        }

        return $this->paginated(
            $query->orderByDesc('sale_date')->paginate($this->perPage()),
            null,
            ['counts' => $this->focusCounts()]
        );
    }

    /**
     * The states an order can be in, as the dashboard names them.
     *
     * One idea, shared: the action list links here with a focus, the filter
     * buttons on the page set the same focus, and the counts beside those
     * buttons are taken from these same sets. There is nowhere left for the
     * number and the rows to disagree.
     */
    private const FOCUS_SETS = [
        'all' => 'Everything',
        'owing' => 'Awaiting payment',
        'processing' => 'Payment to verify',
        'paid' => 'Paid',
        'to_deliver' => 'To hand over',
        'delivered' => 'Delivered',
        'refunds' => 'Refunds to process',
        'cancelled' => 'Cancelled',
    ];

    private function applyFocus($query, ?string $focus): void
    {
        $active = fn ($q) => $q->where('order_status', Sale::STATUS_ACTIVE);

        match ($focus) {
            'owing' => $active($query)->whereIn('payment_status', ['unpaid', 'partial']),
            'processing' => $active($query)->where('payment_status', 'processing'),
            'paid' => $active($query)->where('payment_status', 'paid'),
            'to_deliver' => $active($query)->where('payment_status', 'paid')
                ->where('delivery_status', '!=', 'delivered'),
            'delivered' => $active($query)->where('delivery_status', 'delivered'),
            'refunds' => $query->where('refund_status', Sale::REFUND_PENDING),
            'cancelled' => $query->where('order_status', Sale::STATUS_CANCELLED),
            default => null,
        };
    }

    /** How many orders sit in each state, for the filter buttons. */
    private function focusCounts(): array
    {
        $counts = [];

        foreach (array_keys(self::FOCUS_SETS) as $focus) {
            $query = Sale::query();
            $this->applyFocus($query, $focus === 'all' ? null : $focus);
            $counts[$focus] = $query->count();
        }

        return $counts;
    }

    // Get single sale
    public function getSale($id)
    {
        $sale = Sale::with(['items.product', 'user'])->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    // Create sale
    public function store(Request $request)
    {
        $ceiling = (float) config('payments.maximum_amount');

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,gcash,cash,other',
            'paid_amount' => 'nullable|numeric|min:0|max:' . $ceiling,
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0|max:999999',
        ], [
            'items.max' => 'A single sale can carry at most 100 lines.',
            'items.*.price.max' => 'A unit price cannot exceed PHP 999,999.',
        ]);

        DB::beginTransaction();
        try {
            // Check stock for all items
            foreach ($request->items as $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])->first();
                if (!$inventory || $inventory->quantity < $item['quantity']) {
                    $product = Product::find($item['product_id']);
                    throw new \Exception("Insufficient stock for: " . $product->product_name);
                }
            }

            // Calculate total
            $total = collect($request->items)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            // Each line is bounded on its own, but a hundred of them together
            // are not, and the total_amount column stops short of a hundred
            // million. Caught here so it reads as a message, not a SQL error.
            if ($total > $ceiling) {
                throw new \Exception('That sale totals more than PHP ' . number_format($ceiling, 2) . '. Split it across separate sales.');
            }

            $paidAmount = min((float) ($request->paid_amount ?? 0), (float) $total);
            $paymentStatus = $paidAmount <= 0 ? 'unpaid' : ($paidAmount < $total ? 'partial' : 'paid');

            // Create sale
            $sale = Sale::create([
                'customer_name' => $request->customer_name,
                'user_id' => auth()->id(),
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => $paymentStatus,
                'paid_amount' => $paidAmount,
                'balance_due' => max($total - $paidAmount, 0),
                'delivery_status' => 'to_deliver',
                'sale_date' => now()
            ]);

            // Create sale items and update inventory
            foreach ($request->items as $item) {
                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity']
                ]);

                // Update inventory
                Inventory::updateStock($item['product_id'], -$item['quantity']);

                // Log transaction
                StockTransaction::logTransaction(
                    $item['product_id'],
                    'stock_out',
                    $item['quantity'],
                    'SALE-' . $sale->sale_id,
                    'Admin sale'
                );
            }

            ActivityLog::logAction(
                auth()->id(),
                'sale_created',
                auth()->user()->full_name . " created sale #{$sale->sale_id} for {$sale->customer_name} — total: ₱{$sale->total_amount}, payment: {$sale->payment_status}"
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully',
                'data' => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Get products for sale
    public function getProducts()
    {
        $products = Product::with('inventory')
            ->where(function ($query) {
                $query->whereHas('inventory', function ($q) {
                    $q->where('quantity', '>', 0);
                });
            })
            ->orderBy('product_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found'
            ], 404);
        }

        $request->validate([
            'payment_status' => 'required|in:unpaid,processing,partial,paid,derived',
            'delivery_status' => 'required|in:to_deliver,to_receive,delivered',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $total = (float) $sale->total_amount;
        $paidAmount = (float) ($request->paid_amount ?? $sale->paid_amount ?? 0);
        $paidAmount = min(max($paidAmount, 0), $total);
        $balanceDue = max($total - $paidAmount, 0);

        // Derive payment status from the actual paid amount.
        // Only allow 'processing' to pass through if explicitly set in the request.
        if ($paidAmount <= 0) {
            $paymentStatus = $request->payment_status === 'processing' ? 'processing' : 'unpaid';
        } elseif ($balanceDue <= 0) {
            $paymentStatus = 'paid';
        } else {
            $paymentStatus = $request->payment_status === 'processing' ? 'processing' : 'partial';
        }

        $sale->payment_status = $paymentStatus;
        $sale->delivery_status = $request->delivery_status;
        $sale->paid_amount = $paidAmount;
        $sale->balance_due = $balanceDue;

        if ($request->delivery_status === 'delivered' && $paymentStatus === 'unpaid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot mark an unpaid order as delivered.'
            ], 422);
        }

        $sale->save();

        ActivityLog::logAction(
            auth()->id(),
            'sale_status_updated',
            "Sale #{$sale->sale_id} ({$sale->customer_name}) updated by " . auth()->user()->full_name . " — payment: {$sale->payment_status}, paid: ₱{$sale->paid_amount}, balance: ₱{$sale->balance_due}, delivery: {$sale->delivery_status}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Sale status updated successfully.',
            'data' => $sale,
        ]);
    }

    public function approvePaymentRequest(Request $request, int $id)
    {
        $paymentRequest = PaymentRequest::with('sale')->find($id);

        if (!$paymentRequest || !$paymentRequest->sale) {
            return response()->json([
                'success' => false,
                'message' => 'Payment request not found.'
            ], 404);
        }

        if ($paymentRequest->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Only processing payment requests can be approved.'
            ], 422);
        }

        $sale = $paymentRequest->sale;
        $approvedAmount = min((float) $paymentRequest->amount, (float) $sale->balance_due);

        $sale->paid_amount = min((float) $sale->paid_amount + $approvedAmount, (float) $sale->total_amount);
        $sale->balance_due = max((float) $sale->total_amount - (float) $sale->paid_amount, 0);
        $sale->payment_status = $sale->balance_due <= 0 ? 'paid' : 'partial';
        $sale->payment_method = $paymentRequest->payment_method;
        $sale->save();

        $paymentRequest->status = 'approved';
        $paymentRequest->admin_notes = $request->input('admin_notes');
        $paymentRequest->reviewed_by = auth()->id();
        $paymentRequest->reviewed_at = now();
        $paymentRequest->save();

        // The customer uploaded a receipt and has been waiting on a human to
        // look at it. Telling them is the whole point of the wait ending.
        Notifier::send(
            $sale->user?->email,
            new PaymentReviewed($paymentRequest->fresh('sale')),
            ['payment_request_id' => $paymentRequest->id, 'outcome' => 'approved']
        );

        ActivityLog::logAction(
            auth()->id(),
            'payment_request_approved',
            "Payment request #{$paymentRequest->id} approved by " . auth()->user()->full_name . " for sale #{$sale->sale_id} ({$sale->customer_name}) — amount: ₱{$approvedAmount}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment request approved successfully.',
            'data' => $paymentRequest->fresh(['sale', 'user', 'reviewer']),
        ]);
    }

    public function rejectPaymentRequest(Request $request, int $id)
    {
        $paymentRequest = PaymentRequest::with('sale')->find($id);

        if (!$paymentRequest || !$paymentRequest->sale) {
            return response()->json([
                'success' => false,
                'message' => 'Payment request not found.'
            ], 404);
        }

        if ($paymentRequest->status !== 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Only processing payment requests can be rejected.'
            ], 422);
        }

        $paymentRequest->status = 'rejected';
        $paymentRequest->admin_notes = $request->input('admin_notes');
        $paymentRequest->reviewed_by = auth()->id();
        $paymentRequest->reviewed_at = now();
        $paymentRequest->save();

        $sale = $paymentRequest->sale;
        $sale->payment_status = (float) $sale->paid_amount > 0 ? 'partial' : 'unpaid';
        $sale->save();

        // Worth more than the approval, if anything: a rejection leaves the
        // balance unchanged, and somebody who is not told will assume it went
        // through and be surprised on delivery.
        Notifier::send(
            $sale->user?->email,
            new PaymentReviewed($paymentRequest->fresh('sale')),
            ['payment_request_id' => $paymentRequest->id, 'outcome' => 'rejected']
        );

        ActivityLog::logAction(
            auth()->id(),
            'payment_request_rejected',
            "Payment request #{$paymentRequest->id} rejected by " . auth()->user()->full_name . " for sale #{$sale->sale_id} ({$sale->customer_name})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment request rejected.',
            'data' => $paymentRequest->fresh(['sale', 'user', 'reviewer']),
        ]);
    }

    /**
     * Call off an order from the back office, for a customer who phoned in or
     * an order that cannot be fulfilled.
     */
    public function cancelSale(Request $request, int $id, OrderCancellation $cancellation)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'Give a reason so the cancellation can be explained later.',
        ]);

        $sale = Sale::with('items')->find($id);

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }

        try {
            $sale = $cancellation->cancel($sale, auth()->user(), $request->reason);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $sale->owesRefund()
                ? 'Order cancelled. A PHP ' . number_format((float) $sale->refund_amount, 2) . ' refund is now owed.'
                : 'Order cancelled. Stock has been returned.',
            'data' => $sale->fresh(),
        ]);
    }

    /**
     * Record that a refund owed on a cancelled order has been sent. The
     * transfer itself happens in GCash, outside this system.
     */
    public function recordRefund(Request $request, int $id, OrderCancellation $cancellation)
    {
        $request->validate([
            'reference' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-]*$/'],
            'notes' => 'nullable|string|max:500',
        ], [
            'reference.regex' => 'A reference may use letters, numbers and hyphens only.',
        ]);

        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }

        try {
            $sale = $cancellation->markRefunded($sale, auth()->user(), $request->reference, $request->notes);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Refund recorded.',
            'data' => $sale->fresh(),
        ]);
    }

    /**
     * Attach a handover photo and close the delivery out. Mirrors how a
     * customer proves a payment, so the same evidence trail covers both ends
     * of the transaction.
     */
    public function uploadDeliveryProof(Request $request, int $id)
    {
        $proof = config('payments.proof');

        $request->validate([
            'delivery_proof' => [
                'required',
                'image',
                'mimes:' . implode(',', $proof['mimes']),
                'max:' . $proof['max_kilobytes'],
                'dimensions:min_width=' . $proof['min_width'] . ',min_height=' . $proof['min_height'],
            ],
            'notes' => 'nullable|string|max:500',
        ], [
            'delivery_proof.required' => 'Attach a photo taken at handover.',
            'delivery_proof.dimensions' => 'That image is too small. Upload the full photo, at least '
                . $proof['min_width'] . ' by ' . $proof['min_height'] . ' pixels.',
        ]);

        $sale = Sale::find($id);

        if (!$sale) {
            return response()->json(['success' => false, 'message' => 'Sale not found.'], 404);
        }

        if ($sale->isCancelled()) {
            return response()->json(['success' => false, 'message' => 'This order was cancelled.'], 422);
        }

        // Test against money actually collected rather than the status string:
        // a 'processing' order has a payment claimed but not yet approved, so
        // nothing has been received on it.
        if ((float) $sale->paid_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'No payment has been collected on this order yet. Record what the customer paid before closing it out.',
            ], 422);
        }

        if ($sale->paymentRequests()->where('status', 'processing')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A payment on this order is still awaiting review. Approve or reject it first.',
            ], 422);
        }

        // Replace rather than accumulate, so one order keeps one handover photo.
        if ($sale->delivery_proof_path) {
            Storage::disk('public')->delete($sale->delivery_proof_path);
        }

        $sale->delivery_proof_path = $request->file('delivery_proof')->store('delivery-proofs', 'public');
        $sale->delivered_at = now();
        $sale->delivery_confirmed_by = auth()->id();
        $sale->delivery_status = 'delivered';
        $sale->save();

        Notifier::send(
            $sale->user?->email,
            new OrderDelivered($sale->load('items.product', 'user')),
            ['sale_id' => $sale->sale_id]
        );

        ActivityLog::logAction(
            auth()->id(),
            'delivery_confirmed',
            auth()->user()->full_name . " confirmed delivery of order #{$sale->sale_id} ({$sale->customer_name})"
                . ($request->notes ? " - {$request->notes}" : '')
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery confirmed.',
            'data' => $sale->fresh(),
        ]);
    }
}
