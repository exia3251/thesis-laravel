<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockTransaction;
use App\Models\ActivityLog;
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
        $query = Sale::with(['items.product', 'user']);

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
            'payment_status' => 'required|in:unpaid,partial,paid',
            'delivery_status' => 'required|in:to_deliver,to_receive,delivered',
            'paid_amount' => 'nullable|numeric|min:0',
        ]);

        $paidAmount = (float) ($request->paid_amount ?? $sale->paid_amount ?? 0);
        $paidAmount = min($paidAmount, (float) $sale->total_amount);

        $sale->payment_status = $request->payment_status;
        $sale->delivery_status = $request->delivery_status;
        $sale->paid_amount = $paidAmount;
        $sale->balance_due = max((float) $sale->total_amount - $paidAmount, 0);

        if ($sale->balance_due <= 0 && $sale->payment_status !== 'paid') {
            $sale->payment_status = 'paid';
        }

        if ($sale->payment_status === 'unpaid') {
            $sale->paid_amount = 0;
            $sale->balance_due = (float) $sale->total_amount;
        }

        $sale->save();

        ActivityLog::logAction(
            auth()->id(),
            'sale_status_updated',
            "Updated sale #{$sale->sale_id} to payment {$sale->payment_status} and delivery {$sale->delivery_status}",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Sale status updated successfully.',
            'data' => $sale,
        ]);
    }
}
