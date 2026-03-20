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
            'quantity' => 'required|integer|min:1',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
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
                $request->reference_no,
                $request->notes
            );

            $product = Product::find($request->product_id);
            ActivityLog::logAction(
                auth()->id(),
                'inventory_stock_in',
                "Stocked in {$request->quantity} units for {$product?->product_name}",
                $request->ip()
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
            'quantity' => 'required|integer|min:1',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string'
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
                $request->reference_no,
                $request->notes
            );

            $product = Product::find($request->product_id);
            ActivityLog::logAction(
                auth()->id(),
                'inventory_stock_out',
                "Stocked out {$request->quantity} units for {$product?->product_name}",
                $request->ip()
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
