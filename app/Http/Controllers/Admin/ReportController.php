<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports');
    }

    public function salesReport(Request $request)
    {
        $sales = $this->buildSalesQuery($request)
            ->orderBy('sale_date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'sale_id' => $sale->sale_id,
                    'sale_date' => $sale->sale_date,
                    'customer_name' => $sale->customer_name ?: $sale->user?->full_name,
                    'payment_method' => $sale->payment_method,
                    'payment_status' => $sale->payment_status,
                    'delivery_status' => $sale->delivery_status,
                    'paid_amount' => $sale->paid_amount,
                    'balance_due' => $sale->balance_due,
                    'total_amount' => $sale->total_amount,
                    'item_count' => $sale->items->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sales
        ]);
    }

    public function inventoryReport()
    {
        $inventory = $this->inventoryRows();

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    public function exportSalesCsv(Request $request)
    {
        $sales = $this->buildSalesQuery($request)
            ->orderBy('sale_date', 'desc')
            ->get();

        $filename = 'sales-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Sale ID',
                'Date',
                'Customer',
                'Payment Method',
                'Payment Status',
                'Delivery Status',
                'Paid Amount',
                'Balance Due',
                'Total Amount',
                'Items',
            ]);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->sale_id,
                    optional($sale->sale_date)->format('Y-m-d H:i:s'),
                    $sale->customer_name ?: $sale->user?->full_name,
                    $sale->payment_method,
                    $sale->payment_status,
                    $sale->delivery_status,
                    $sale->paid_amount,
                    $sale->balance_due,
                    $sale->total_amount,
                    $sale->items->count(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function exportInventoryCsv()
    {
        $inventory = $this->inventoryRows();
        $filename = 'inventory-report-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($inventory) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Product Name',
                'Brand',
                'Unit',
                'Price',
                'Quantity',
                'Inventory Value',
                'Reorder Level',
                'Status',
            ]);

            foreach ($inventory as $row) {
                fputcsv($handle, [
                    $row['product_name'],
                    $row['brand'],
                    $row['unit'],
                    $row['price'],
                    $row['quantity'],
                    $row['value'],
                    $row['reorder_level'],
                    $row['status'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function buildSalesQuery(Request $request)
    {
        $query = Sale::with(['items.product', 'user']);

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('sale_date', [
                $request->input('from') . ' 00:00:00',
                $request->input('to') . ' 23:59:59',
            ]);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('delivery_status')) {
            $query->where('delivery_status', $request->input('delivery_status'));
        }

        return $query;
    }

    protected function inventoryRows()
    {
        return Product::with('inventory')
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) {
                $quantity = $product->inventory->quantity ?? 0;

                return [
                    'product_name' => $product->product_name,
                    'brand' => $product->brand,
                    'unit' => $product->unit,
                    'price' => $product->price,
                    'quantity' => $quantity,
                    'value' => $quantity * $product->price,
                    'reorder_level' => $product->reorder_level,
                    'status' => $quantity <= $product->reorder_level ? 'Low Stock' : 'In Stock'
                ];
            });
    }
}
