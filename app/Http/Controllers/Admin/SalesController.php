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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    // Display sales page
    public function index()
    {
        return view('admin.sales');
    }

    // Get all sales
    public function getSales(Request $request)
    {
        $query = Sale::with(['items.product', 'user', 'paymentRequests.user', 'paymentRequests.reviewer']);

        // Filter by date range if provided
        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('sale_date', [$request->from, $request->to]);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
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
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,gcash,cash,other',
            'paid_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'items.*.price' => 'required|numeric|min:0'
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
                auth()->user()->full_name . " created sale #{$sale->sale_id} for {$sale->customer_name} — total: ₱{$sale->total_amount}, payment: {$sale->payment_status}",
                $request->ip()
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
            "Sale #{$sale->sale_id} ({$sale->customer_name}) updated by " . auth()->user()->full_name . " — payment: {$sale->payment_status}, paid: ₱{$sale->paid_amount}, balance: ₱{$sale->balance_due}, delivery: {$sale->delivery_status}",
            $request->ip()
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

        ActivityLog::logAction(
            auth()->id(),
            'payment_request_approved',
            "Payment request #{$paymentRequest->id} approved by " . auth()->user()->full_name . " for sale #{$sale->sale_id} ({$sale->customer_name}) — amount: ₱{$approvedAmount}",
            $request->ip()
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

        ActivityLog::logAction(
            auth()->id(),
            'payment_request_rejected',
            "Payment request #{$paymentRequest->id} rejected by " . auth()->user()->full_name . " for sale #{$sale->sale_id} ({$sale->customer_name})",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment request rejected.',
            'data' => $paymentRequest->fresh(['sale', 'user', 'reviewer']),
        ]);
    }
}