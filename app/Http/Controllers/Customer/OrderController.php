<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ShoppingCart;
use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Display checkout page
    public function checkout()
    {
        return view('customer.checkout');
    }

    // Display orders page
    public function index()
    {
        return view('customer.orders');
    }

    // Place order
    public function placeOrder(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Get cart items
            $cartItems = ShoppingCart::with('product')
                ->where('user_id', auth()->id())
                ->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty'
                ], 400);
            }

            // Check stock for all items
            foreach ($cartItems as $item) {
                $inventory = Inventory::where('product_id', $item->product_id)->first();
                if (!$inventory || $inventory->quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for: " . $item->product->product_name);
                }
            }

            // Get customer profile
            $profile = CustomerProfile::where('user_id', auth()->id())->first();

            // Calculate total
            $total = $cartItems->sum(function ($item) {
                return $item->product->price * $item->quantity;
            });

            // Create sale
            $sale = Sale::create([
                'customer_name' => auth()->user()->full_name,
                'user_id' => auth()->id(),
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'status' => 'Pending',
                'sale_date' => now()
            ]);

            // Create sale items and update inventory
            foreach ($cartItems as $item) {
                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->sale_id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                    'subtotal' => $item->product->price * $item->quantity
                ]);

                // Update inventory
                Inventory::updateStock($item->product_id, -$item->quantity);

                // Log transaction
                StockTransaction::logTransaction(
                    $item->product_id,
                    'SALE',
                    $item->quantity,
                    'ORDER-' . $sale->sale_id,
                    'Customer order'
                );
            }

            // Clear cart
            ShoppingCart::where('user_id', auth()->id())->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => ['order_id' => $sale->sale_id]
            ]);

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
        $orders = Sale::with('items.product')
            ->where('user_id', auth()->id())
            ->orderBy('sale_date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'sale_id' => $sale->sale_id,
                    'sale_date' => $sale->sale_date,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'status' => $sale->status,
                    'item_count' => $sale->items->count()
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
        $sale = Sale::with('items.product')
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
}