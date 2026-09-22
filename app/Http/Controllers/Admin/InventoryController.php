<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    // Display inventory page
    public function index()
    {
        return view('admin.inventory');
    }

    // Get all inventory
    public function getInventory()
    {
        $inventory = Product::with('inventory')
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'brand' => $product->brand,
                    'unit' => $product->unit,
                    'quantity' => $product->inventory->quantity ?? 0,
                    'reorder_level' => $product->reorder_level,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    // Stock in
    public function stockIn(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1|max:100000',
        ], [
            'quantity.max' => 'Stock is added in movements of at most 100,000 units.',
        ]);

        DB::beginTransaction();
        try {
            // Update inventory
            Inventory::updateStock($request->product_id, $request->quantity);

            // Log transaction
            StockTransaction::logTransaction(
                $request->product_id,
                'IN',
                $request->quantity,

            );

            $product = Product::find($request->product_id);

            ActivityLog::logAction(
                auth()->id(),
                'stock_in',
                "Stock in: {$request->quantity} units of {$product->product_name} by " . auth()->user()->full_name
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add stock: ' . $e->getMessage()
            ], 500);
        }
    }

    // Stock out
    public function stockOut(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer|min:1|max:100000',
        ], [
            'quantity.max' => 'Stock is removed in movements of at most 100,000 units.',
        ]);

        DB::beginTransaction();
        try {
            $inventory = Inventory::where('product_id', $request->product_id)->first();

            if (!$inventory || $inventory->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }

            // Update inventory
            Inventory::updateStock($request->product_id, -$request->quantity);

            // Log transaction
            StockTransaction::logTransaction(
                $request->product_id,
                'OUT',
                $request->quantity,

            );

            $product = Product::find($request->product_id);

            ActivityLog::logAction(
                auth()->id(),
                'stock_out',
                "Stock out: {$request->quantity} units of {$product->product_name} by " . auth()->user()->full_name
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove stock: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get stock transactions for a product
    public function getTransactions($productId)
    {
        $transactions = StockTransaction::with('user')
            ->where('product_id', $productId)
            ->orderBy('transaction_date', 'desc')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}