<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Figures behind the analytics screen.
 *
 * Two different numbers get called "sales" in this trade and they are not the
 * same: what was booked (the value of orders taken) and what was collected
 * (money actually received). An order on a down payment counts fully towards
 * the first and partly towards the second, so both are reported rather than
 * picking one and calling it revenue.
 *
 * Cancelled orders are excluded everywhere. They were called off, so counting
 * them would overstate trade.
 */
class AnalyticsController extends Controller
{
    public function index()
    {
        return view('admin.analytics');
    }

    public function data(Request $request)
    {
        $request->validate(['months' => 'nullable|integer|min:3|max:24']);

        $months = (int) $request->get('months', 12);
        $to = now()->endOfDay();
        $from = now()->copy()->subMonths($months - 1)->startOfMonth();

        // The same span again, immediately before, for comparison.
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subMonths($months - 1)->startOfMonth();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                    'label' => $from->format('M Y') . ' - ' . $to->format('M Y'),
                    'months' => $months,
                ],
                'headline' => $this->headline($from, $to, $previousFrom, $previousTo),
                'monthly' => $this->monthly($from, $to),
                'top_products' => $this->topProducts($from, $to),
                'by_brand' => $this->byBrand($from, $to),
                'payment_mix' => $this->paymentMix($from, $to),
                'receivables' => $this->receivables(),
            ],
        ]);
    }

    private function activeBetween(Carbon $from, Carbon $to)
    {
        return Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sale_date', [$from, $to]);
    }

    private function headline(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $now = $this->activeBetween($from, $to)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected')
            ->first();

        $was = $this->activeBetween($previousFrom, $previousTo)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected')
            ->first();

        $cancelled = Sale::where('order_status', Sale::STATUS_CANCELLED)
            ->whereBetween('sale_date', [$from, $to])
            ->count();

        $averageNow = $now->orders ? (float) $now->booked / $now->orders : 0.0;
        $averageWas = $was->orders ? (float) $was->booked / $was->orders : 0.0;

        return [
            'collected' => $this->withDelta((float) $now->collected, (float) $was->collected),
            'booked' => $this->withDelta((float) $now->booked, (float) $was->booked),
            'orders' => $this->withDelta((int) $now->orders, (int) $was->orders),
            'average_order' => $this->withDelta($averageNow, $averageWas),
            'cancelled_orders' => $cancelled,
            'cancellation_rate' => $now->orders + $cancelled > 0
                ? round($cancelled / ($now->orders + $cancelled) * 100, 1)
                : 0,
            // What share of everything booked has actually been collected.
            'collection_rate' => (float) $now->booked > 0
                ? round((float) $now->collected / (float) $now->booked * 100, 1)
                : 0,
        ];
    }

    /** A value beside the same value last period, and the move between them. */
    private function withDelta(float|int $current, float|int $previous): array
    {
        $change = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        return [
            'value' => $current,
            'previous' => $previous,
            'delta_percent' => $change,
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        ];
    }

    private function monthly(Carbon $from, Carbon $to): array
    {
        $rows = $this->activeBetween($from, $to)
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') period, COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Walk every month in range so a quiet one is a gap in the chart
        // rather than a missing bar that shifts everything along.
        $series = [];

        for ($month = $from->copy()->startOfMonth(); $month->lte($to); $month->addMonth()) {
            $key = $month->format('Y-m');
            $row = $rows->get($key);

            $series[] = [
                'period' => $key,
                'label' => $month->format('M'),
                'full_label' => $month->format('F Y'),
                'orders' => (int) ($row->orders ?? 0),
                'booked' => round((float) ($row->booked ?? 0), 2),
                'collected' => round((float) ($row->collected ?? 0), 2),
            ];
        }

        return $series;
    }

    private function topProducts(Carbon $from, Carbon $to): array
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.sale_id', '=', 'sale_items.sale_id')
            ->join('products', 'products.product_id', '=', 'sale_items.product_id')
            ->where('sales.order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('products.product_id', 'products.product_name', 'products.brand', 'products.unit', 'products.image_path')
            ->selectRaw('products.product_id, products.product_name, products.brand, products.unit, products.image_path,
                         SUM(sale_items.quantity) units, SUM(sale_items.subtotal) revenue')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'name' => $row->product_name,
                'brand' => $row->brand,
                'unit' => $row->unit,
                'image_url' => $row->image_path ? asset('storage/' . $row->image_path) : null,
                'units' => (int) $row->units,
                'revenue' => round((float) $row->revenue, 2),
            ])
            ->all();
    }

    private function byBrand(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sales.sale_id', '=', 'sale_items.sale_id')
            ->join('products', 'products.product_id', '=', 'sale_items.product_id')
            ->where('sales.order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy('products.brand')
            ->selectRaw('products.brand, SUM(sale_items.subtotal) revenue, SUM(sale_items.quantity) units')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(fn ($row) => [
            'brand' => $row->brand,
            'revenue' => round((float) $row->revenue, 2),
            'units' => (int) $row->units,
            'share' => $total > 0 ? round((float) $row->revenue / $total * 100, 1) : 0,
        ])->all();
    }

    private function paymentMix(Carbon $from, Carbon $to): array
    {
        $rows = $this->activeBetween($from, $to)
            ->selectRaw('payment_plan, COUNT(*) orders, COALESCE(SUM(total_amount),0) booked')
            ->groupBy('payment_plan')
            ->get()
            ->keyBy('payment_plan');

        $total = (int) $rows->sum('orders');

        return collect(Sale::PLAN_LABELS)
            ->map(function (string $label, string $plan) use ($rows, $total) {
                $row = $rows->get($plan);
                $orders = (int) ($row->orders ?? 0);

                return [
                    'plan' => $plan,
                    'label' => $label,
                    'orders' => $orders,
                    'booked' => round((float) ($row->booked ?? 0), 2),
                    'share' => $total > 0 ? round($orders / $total * 100, 1) : 0,
                ];
            })
            ->values()
            ->all();
    }

    /** What is still owed across every live order, whenever it was placed. */
    private function receivables(): array
    {
        $row = Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->where('balance_due', '>', 0)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(balance_due),0) outstanding')
            ->first();

        $awaitingReview = DB::table('payment_requests')->where('status', 'processing')->count();

        return [
            'outstanding' => round((float) $row->outstanding, 2),
            'orders' => (int) $row->orders,
            'awaiting_review' => $awaitingReview,
        ];
    }
}
