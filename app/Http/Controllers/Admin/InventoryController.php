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
            ->orderBy('price')
            ->get()
            ->map(function ($product) {
                $quantity = (int) ($product->inventory->quantity ?? 0);
                $reorder = (int) $product->reorder_level;

                return [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'brand' => $product->brand,
                    // The three Patrol 5W30 rows are one name and three pack
                    // sizes, so without this they are the same line thrice.
                    'unit' => $product->unit,
                    'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                    'price' => (float) $product->price,
                    'stock_value' => round($quantity * (float) $product->price, 2),
                    'quantity' => $quantity,
                    'reorder_level' => $reorder,
                    /*
                     * Worked out here rather than in the browser, because the
                     * dashboard counts "running low" as at or under the
                     * reorder level and this screen used to count it as half
                     * of that. The dashboard's action list links straight
                     * here, so the two saying different things meant clicking
                     * "7 products running low" could land on four.
                     */
                    'status' => $quantity <= 0 ? 'out' : ($quantity <= $reorder ? 'low' : 'ok'),
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