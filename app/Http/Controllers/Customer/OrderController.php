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
    protected function amountRuleViolation(Sale $sale, float $amount): ?string
    {
        $outstanding = $sale->gcashOutstanding();

        if ($outstanding > 0) {
            if (abs($amount - $outstanding) > 0.01) {
                return 'This order has a GCash payment of PHP ' . number_format($outstanding, 2)
                    . ' due. Send that exact amount in a single transfer.';
            }

            return null;
        }

        $balance = (float) $sale->balance_due;
        $floor = min((float) config('payments.minimum_extra_payment', 500), $balance);

        if ($amount < $floor) {
            return 'Early payments must be at least PHP ' . number_format($floor, 2)
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
            'gcash_amount' => 'nullable|numeric|min:0',
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

        // Get customer profile before opening a transaction
        $profile = CustomerProfile::where('user_id', auth()->id())->first();

        if (!$profile || blank($profile->address) || blank($profile->phone)) {
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

            $gcashAmount = $this->resolveGcashAmount($plan, $total, $request);

            // Create sale
            $sale = Sale::create([
                'customer_name' => auth()->user()->full_name,
                'user_id' => auth()->id(),
                'delivery_address' => $profile->address,
                'contact_phone' => $profile->phone,
                'total_amount' => $total,
                'payment_method' => $plan === Sale::PLAN_COD ? 'cash_on_delivery' : 'gcash',
                'payment_plan' => $plan,
                'gcash_amount' => $gcashAmount,
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
                'balance_due' => $total,
                'delivery_status' => 'to_receive',
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
        $orders = Sale::with(['items.product', 'paymentRequests'])
            ->where('user_id', auth()->id())
            ->orderBy('sale_date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'sale_id' => $sale->sale_id,
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
                    'item_count' => $sale->items->count(),
                    'processing_requests' => $sale->paymentRequests->where('status', 'processing')->count(),
                    'status_group' => $sale->payment_status !== 'paid'
                        ? 'to_pay'
                        : ($sale->delivery_status === 'delivered' ? 'delivered' : 'to_receive'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
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

    public function submitPaymentRequest(Request $request, $saleId)
    {
        $sale = Sale::where('sale_id', $saleId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $proof = config('payments.proof');

        $request->validate([
            'payment_method' => 'required|in:gcash',
            'amount' => 'required|numeric|min:1',
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
