<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard');
    }

    public function getStats(Request $request)
    {
        $days = $request->get('days', 30);
        $dateFrom = now()->subDays($days)->startOfDay();
        $dateTo = now()->endOfDay();

        // Total products
        $totalProducts = Product::count();

        // Total inventory value
        $inventoryValue = Product::join('inventory', 'products.product_id', '=', 'inventory.product_id')
            ->selectRaw('SUM(products.price * inventory.quantity) as total')
            ->value('total') ?? 0;

        // Low stock count
        $lowStockCount = Product::join('inventory', 'products.product_id', '=', 'inventory.product_id')
            ->whereRaw('inventory.quantity <= products.reorder_level')
            ->count();

        // Sales stats for period
        $salesStats = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
            ->first();

        // Sales trend
        $salesTrend = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->selectRaw('DATE(sale_date) as date, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top products
        $topProducts = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.sale_id')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->selectRaw('products.product_name, SUM(sale_items.quantity) as total_sold')
            ->groupBy('products.product_id', 'products.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_products' => $totalProducts,
                'inventory_value' => $inventoryValue,
                'low_stock_count' => $lowStockCount,
                'sales_count' => $salesStats->count ?? 0,
                'sales_revenue' => $salesStats->revenue ?? 0,
                'sales_trend' => $salesTrend,
                'top_products' => $topProducts
            ]
        ]);
    }
}