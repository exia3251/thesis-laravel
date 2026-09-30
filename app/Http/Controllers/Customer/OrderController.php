<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShoppingCart;
use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\CustomerProfile;
use App\Models\PaymentRequest;
use App\Models\ActivityLog;
use App\Mail\OrderPlaced;
use App\Services\Notifier;
use App\Services\OrderCancellation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    // Display orders page
    public function index()
    {
        return view('customer.orders');
    }

    public function show($saleId)
    {
        $sale = Sale::with(['items.product', 'user', 'paymentRequests.reviewer'])
            ->where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('customer.order-details', compact('sale'));
    }

    /**
     * Whether a submitted payment amount breaks the settlement rules, as a
     * message to show the customer. Null when the amount is acceptable.
     *
     * An outstanding GCash commitment is settled in one transfer for its whole
     * value: letting it arrive in fragments buys the customer nothing and costs
     * an administrator a review for each piece. Once that commitment is met,
     * paying the delivery balance down early is optional, so it carries a floor
     * instead - except when the customer is clearing the balance outright.
     */
    /**
     * What a customer is allowed to send at this point in the order.
     *
     * Anything from the floor up to what is still owed. The down payment used
     * to have to arrive as one transfer of exactly the committed amount,
     * which was a dead end rather than a rule: a GCash wallet holds PHP
     * 100,000 when fully verified, so the online half of a large order could
     * not be sent at all, in one go or otherwise. Paying it in parts is now
     * the same arrangement as paying a delivery balance down early, and each
     * part is reviewed as its own receipt.
     *
     * The order still waits on the whole committed amount before it is
     * treated as paid; this only changes how many transfers it may arrive in.
     */
    protected function amountRuleViolation(Sale $sale, float $amount): ?string
    {
        $balance = (float) $sale->balance_due;
        $floor = min((float) config('payments.minimum_extra_payment', 500), $balance);

        if ($amount > $balance + 0.01) {
            return 'That is more than the PHP ' . number_format($balance, 2) . ' still owed on this order.';
        }

        if ($amount + 0.01 < $floor) {
            return 'Payments must be at least PHP ' . number_format($floor, 2)
                . ', or you can settle the remaining PHP ' . number_format($balance, 2) . ' in full.';
        }

        return null;
    }

    /**
     * Resolves the chosen payment plan into the amount committed to GCash,
     * rejecting a split that falls under the configured down payment floor.
     */
    protected function resolveGcashAmount(string $plan, float $total, Request $request): float
    {
        if ($plan === Sale::PLAN_COD) {
            return 0.0;
        }

        if ($plan === Sale::PLAN_GCASH_FULL) {
            return $total;
        }

        $minimum = Sale::minimumDownPayment($total);
        $amount = round((float) $request->input('gcash_amount', 0), 2);

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'gcash_amount' => 'The down payment must be at least PHP ' . number_format($minimum, 2)
                    . ' (' . config('payments.minimum_down_payment_percent') . '% of the order).',
            ]);
        }

        if ($amount >= $total) {
            throw ValidationException::withMessages([
                'gcash_amount' => 'A split payment must leave a balance for delivery. Choose full GCash payment instead.',
            ]);
        }

        return $amount;
    }

    // Place order
    public function placeOrder(Request $request)
    {
        $request->validate([
            'payment_plan' => 'required|in:cod,gcash_full,split',
            'gcash_amount' => 'nullable|numeric|min:0|max:' . (float) config('payments.maximum_amount'),
        ], [
            'payment_plan.required' => 'Choose how you would like to pay.',
            'payment_plan.in' => 'Choose a valid payment option.',
        ]);

        // Get cart items
        $cartItems = ShoppingCart::with('product')
            ->where('user_id', auth()->id())
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty.'
            ], 400);
        }

        // An unconfirmed address cannot receive a receipt or a refund notice,
        // and it is the same address a Google sign-in would later be linked to.
        if (!auth()->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Confirm your email address before placing an order. Check your inbox, or resend the link from your profile.',
                'needs_verification' => true,
            ], 422);
        }

        // Get customer profile before opening a transaction
        $profile = CustomerProfile::where('user_id', auth()->id())->first();

        if (!$profile || !$profile->isComplete()) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete your profile address and phone before placing an order.'
            ], 422);
        }

        $plan = $request->payment_plan;

        DB::beginTransaction();
        try {
            // Lock the inventory rows before reading them, so two orders placed
            // at the same moment cannot both pass the stock check and oversell.
            foreach ($cartItems as $item) {
                if (!$item->product) {
                    throw new \Exception('One or more items in your cart are no longer available.');
                }

                $inventory = Inventory::where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$inventory || $inventory->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for: " . $item->product->product_name);
                }
            }

            // Calculate total
            $total = (float) $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            // The money columns are decimal(10,2). A basket large enough to
            // pass that should be handled as a quoted order, not silently
            // rejected by the database.
            $ceiling = (float) config('payments.maximum_amount');

            if ($total > $ceiling) {
                throw new \Exception('This basket totals more than PHP ' . number_format($ceiling, 2)
                    . '. Please contact us directly to place an order this size.');
            }

            $gcashAmount = $this->resolveGcashAmount($plan, $total, $request);

            // Create sale
            $sale = Sale::create([
                'customer_name' => auth()->user()->full_name,
                'user_id' => auth()->id(),
                'delivery_address' => $profile->fullAddress(),
                'contact_phone' => $profile->phone,
                'total_amount' => $total,
                'payment_method' => $plan === Sale::PLAN_COD ? 'cash_on_delivery' : 'gcash',
                'payment_plan' => $plan,
                'gcash_amount' => $gcashAmount,
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'balance_due' => $total,
                /*
                 * Processing, not already with a courier. An order was
                 * written straight to to_receive at checkout, before anybody
                 * had been near a shelf, so the back office showed every new
                 * order as though it had already been handed over. Staff move
                 * it on when it actually leaves.
                 */
                'delivery_status' => 'to_deliver',
                'sale_date' => now()
            ]);

            // Create sale items and update inventory
            foreach ($cartItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                    'subtotal' => $item->product->price * $item->quantity
                ]);

                Inventory::updateStock($item->product_id, -$item->quantity);

                StockTransaction::logTransaction(
                    $item->product_id,
                    'stock_out',
                    $item->quantity,
                    'ORDER-' . $sale->sale_id,
                    'Customer order'
                );
            }

            // Clear cart
            ShoppingCart::where('user_id', auth()->id())->delete();

            DB::commit();

            // After the commit, never inside it: a mail failure must not roll
            // back an order that was placed successfully.
            Notifier::send(
                auth()->user()->email,
                new OrderPlaced($sale->load('items.product')),
                ['sale_id' => $sale->sale_id]
            );

            $messages = [
                Sale::PLAN_COD => 'Order placed. Please prepare payment for delivery.',
                Sale::PLAN_GCASH_FULL => 'Order placed. Continue to GCash payment instructions.',
                Sale::PLAN_SPLIT => 'Order placed. Pay your down payment by GCash, then settle the balance on delivery.',
            ];

            return response()->json([
                'success' => true,
                'message' => $messages[$plan],
                'data' => [
                    'order_id' => $sale->sale_id,
                    'payment_plan' => $plan,
                    'payment_method' => $sale->payment_method,
                    'gcash_amount' => $gcashAmount,
                    'cod_amount' => $sale->codAmount(),
                ]
            ]);

        } catch (ValidationException $e) {
            DB::rollback();
            throw $e;
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Get user's orders
    public function getOrders()
    {
        $page = Sale::with(['items.product', 'paymentRequests'])
            ->where('user_id', auth()->id())
            ->orderByDesc('sale_date')
            ->paginate($this->perPage(10));

        return $this->paginated($page, function ($sale) {
                return [
                    'sale_id' => $sale->sale_id,
                    'receipt_no' => $sale->receipt_no,
                    'delivery_no' => $sale->delivery_no,
                    'sale_date' => $sale->sale_date,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'payment_plan' => $sale->payment_plan,
                    'plan_label' => $sale->planLabel(),
                    'gcash_amount' => $sale->gcash_amount,
                    'cod_amount' => $sale->codAmount(),
                    'payment_status' => $sale->payment_status,
                    'paid_amount' => $sale->paid_amount,
                    'balance_due' => $sale->balance_due,
                    'delivery_status' => $sale->delivery_status,
                    'order_status' => $sale->order_status,
                    'status_label' => $sale->statusLabel(),
                    'refund_status' => $sale->refund_status,
                    'refund_amount' => $sale->refund_amount,
                    'received_at' => $sale->received_at,
                    'can_cancel' => $sale->canBeCancelledByCustomer(),
                    'can_confirm_receipt' => $sale->canConfirmReceipt(),
                    'item_count' => $sale->items->count(),
                    'processing_requests' => $sale->paymentRequests->where('status', 'processing')->count(),
                    'status_group' => $sale->statusGroup(),
                ];
        });
    }

    // Get order details
    public function getOrderDetails($saleId)
    {
        $sale = Sale::with(['items.product', 'paymentRequests.reviewer'])
            ->where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale
        ]);
    }

    /**
     * Cancel an order the customer no longer wants.
     */
    public function cancelOrder(Request $request, $saleId, OrderCancellation $cancellation)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $sale = Sale::with('items')
            ->where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($sale->isCancelled()) {
            return response()->json(['success' => false, 'message' => 'This order is already cancelled.'], 422);
        }

        if ($sale->isDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'This order has already been delivered. Contact us if there is a problem with it.',
            ], 422);
        }

        if (!$sale->canBeCancelledByCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Your payment is still being reviewed. Wait for that to finish before cancelling.',
            ], 422);
        }

        try {
            $sale = $cancellation->cancel($sale, auth()->user(), $request->reason);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $sale->owesRefund()
                ? 'Order cancelled. Your PHP ' . number_format((float) $sale->refund_amount, 2) . ' payment will be refunded to your GCash.'
                : 'Order cancelled.',
            'data' => ['refund_owed' => (float) $sale->refund_amount],
        ]);
    }

    /**
     * The customer's own acknowledgement that the goods arrived. An order is
     * only closed out when nothing is still owed on it, so a cash-on-delivery
     * balance still has to be recorded by staff.
     */
    /**
     * A refund asked for on an order that has already arrived.
     *
     * Cancelling is for an order that has not been sent. Once it has been
     * delivered there is nothing to call back, so the customer asks for their
     * money instead, and the order joins the "Refunds to process" list that
     * staff already work from. Nothing is returned automatically: somebody
     * here decides, and records having sent it.
     */
    public function requestRefund(Request $request, $saleId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $sale = Sale::where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($sale->isCancelled()) {
            return response()->json(['success' => false, 'message' => 'This order was cancelled.'], 422);
        }

        if (! $sale->isDelivered()) {
            return response()->json([
                'success' => false,
                'message' => 'This order has not been delivered yet. You can still cancel it instead.',
            ], 422);
        }

        if ($sale->refund_status !== Sale::REFUND_NONE) {
            return response()->json([
                'success' => false,
                'message' => $sale->refund_status === Sale::REFUND_DONE
                    ? 'A refund has already been sent for this order.'
                    : 'A refund has already been asked for on this order.',
            ], 422);
        }

        $paid = (float) $sale->paid_amount;

        if ($paid <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing has been paid on this order, so there is nothing to refund.',
            ], 422);
        }

        $sale->refund_status = Sale::REFUND_PENDING;
        $sale->refund_amount = $paid;
        $sale->refund_reason = $request->input('reason');
        $sale->save();

        ActivityLog::logAction(
            auth()->id(),
            'refund_requested',
            'Customer asked for a PHP ' . number_format($paid, 2) . ' refund on order #' . $sale->sale_id . '.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Your refund request has been sent. Our staff will be in touch.',
        ]);
    }

    public function confirmReceipt(Request $request, $saleId)
    {
        $sale = Sale::where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($sale->isCancelled()) {
            return response()->json(['success' => false, 'message' => 'This order was cancelled.'], 422);
        }

        if ($sale->received_at) {
            return response()->json(['success' => false, 'message' => 'You have already confirmed this order.'], 422);
        }

        /*
         * Nothing can be received while it is still being packed.
         *
         * This was missing, and confirming went further than acknowledging:
         * with nothing owed it also set the order delivered. So a customer
         * could mark their own order delivered while the goods were still on
         * the shelf here, and the warehouse would see a completed order for
         * something nobody had sent.
         */
        if (! $sale->canConfirmReceipt()) {
            return response()->json([
                'success' => false,
                'message' => 'This order has not been sent out yet. You can confirm it once it is with the courier.',
            ], 422);
        }

        $sale->received_at = now();
        $settled = (float) $sale->balance_due <= 0;

        if ($settled) {
            $sale->delivery_status = 'delivered';
            $sale->delivered_at = $sale->delivered_at ?: now();
        }

        $sale->save();

        ActivityLog::logAction(
            auth()->id(),
            'order_received',
            "Customer {$sale->customer_name} confirmed receipt of order #{$sale->sale_id}"
        );

        return response()->json([
            'success' => true,
            'message' => $settled
                ? 'Thank you. This order is now complete.'
                : 'Receipt confirmed. The remaining PHP ' . number_format((float) $sale->balance_due, 2) . ' is still due and will be recorded by our staff.',
        ]);
    }

    public function submitPaymentRequest(Request $request, $saleId)
    {
        $sale = Sale::where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $proof = config('payments.proof');

        $request->validate([
            'payment_method' => 'required|in:gcash',
            'amount' => 'required|numeric|min:1|max:' . (float) config('payments.maximum_amount'),
            'reference_no' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-]{5,99}$/'],
            'proof_image' => [
                'required',
                'image',
                'mimes:' . implode(',', $proof['mimes']),
                'max:' . $proof['max_kilobytes'],
                'dimensions:min_width=' . $proof['min_width'] . ',min_height=' . $proof['min_height'],
            ],
        ], [
            'reference_no.required' => 'Enter the GCash reference number from your receipt.',
            'reference_no.regex' => 'Reference numbers are at least 6 characters, using letters, numbers and hyphens only.',
            'proof_image.required' => 'Attach a screenshot of your GCash receipt.',
            'proof_image.image' => 'The attachment must be an image file.',
            'proof_image.mimes' => 'Accepted formats are ' . strtoupper(implode(', ', $proof['mimes'])) . '.',
            'proof_image.max' => 'The screenshot must be smaller than ' . round($proof['max_kilobytes'] / 1024) . ' MB.',
            'proof_image.dimensions' => 'That image is too small to read. Upload the full screenshot, at least '
                . $proof['min_width'] . ' by ' . $proof['min_height'] . ' pixels.',
        ]);

        if ((float) $sale->balance_due <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This order is already fully paid.'
            ], 422);
        }

        if ($sale->paymentRequests()->where('status', 'processing')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'A payment request is already processing for this order.'
            ], 422);
        }

        // A reference number identifies one GCash transaction, so reusing one
        // means either a mistake or an attempt to claim the same payment twice.
        $referenceInUse = PaymentRequest::where('reference_no', $request->reference_no)
            ->where('status', '!=', 'rejected')
            ->exists();

        if ($referenceInUse) {
            return response()->json([
                'success' => false,
                'message' => 'That reference number has already been submitted. Check your receipt and try again.'
            ], 422);
        }

        if ($error = $this->amountRuleViolation($sale, (float) $request->amount)) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], 422);
        }

        $amount = min((float) $request->amount, (float) $sale->balance_due);

        $proofPath = $request->file('proof_image')->store('payment-proofs', 'public');

        $paymentRequest = PaymentRequest::create([
            'sale_id' => $sale->sale_id,
            'user_id' => auth()->id(),
            'payment_method' => $request->payment_method,
            'amount' => $amount,
            'status' => 'processing',
            'reference_no' => $request->reference_no,
            'proof_image_path' => $proofPath,
        ]);

        $sale->payment_method = $request->payment_method;
        $sale->payment_status = 'processing';
        $sale->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment request submitted. Please wait for admin confirmation.',
            'data' => $paymentRequest,
        ]);
    }
}
