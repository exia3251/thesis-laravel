<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports');
    }

    public function salesReport(Request $request)
    {
        $query = Sale::with(['items.product']);

        if ($request->has('from') && $request->has('to')) {
            $query->whereBetween('sale_date', [$request->from, $request->to]);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function inventoryReport()
    {
        $inventory = Product::with(['inventory', 'supplier'])
            ->get()
            ->map(function ($product) {
                return [
                    'product_name' => $product->product_name,
                    'brand' => $product->brand,
                    'unit' => $product->unit,
                    'price' => $product->price,
                    'quantity' => $product->inventory->quantity ?? 0,
                    'value' => ($product->inventory->quantity ?? 0) * $product->price,
                    'reorder_level' => $product->reorder_level,
                    'status' => ($product->inventory->quantity ?? 0) <= $product->reorder_level ? 'Low Stock' : 'In Stock'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }
}