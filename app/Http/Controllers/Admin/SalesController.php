<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockTransaction;
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
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity' => 'required|integer|min:1',
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

            // Create sale
            $sale = Sale::create([
                'customer_name' => $request->customer_name,
                'user_id' => auth()->id(),
                'total_amount' => $total,
                'payment_method' => $request->payment_method,
                'status' => 'Completed',
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
                    'SALE',
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
            ->where(function($query) {
                $query->whereHas('inventory', function($q) {
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
}