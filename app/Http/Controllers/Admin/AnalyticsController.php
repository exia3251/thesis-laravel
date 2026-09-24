<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesReportRange;
use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Figures behind the analytics screen.
 *
 * Two different numbers get called "sales" in this trade and they are not the
 * same: the value of the orders taken, and the money actually received. An
 * order on a down payment counts fully towards the first and partly towards
 * the second, so both are reported rather than picking one and calling it
 * revenue. What separates them is the money still owed.
 *
 * Cancelled orders are excluded everywhere. They were called off, so counting
 * them would overstate trade.
 *
 * Every figure on this screen is measured over the selected period, including
 * the money owed, which is the balance left on the orders placed in it.
 */
class AnalyticsController extends Controller
{
    use ResolvesReportRange;

    public function index()
    {
        return view('admin.analytics');
    }

    public function data(Request $request)
    {
        $request->validate($this->rangeRules(), $this->rangeMessages());

        $range = $this->resolveRange($request, '12m');
        $from = $range['from'];
        $to = $range['to'];

        return response()->json([
            'success' => true,
            'data' => [
                'range' => $this->rangePayload($range),
                'headline' => $this->headline($range),
                'series' => $this->series($range),
                'top_products' => $this->topProducts($from, $to),
                'by_brand' => $this->byBrand($from, $to),
                'breakdown' => [
                    'product' => $this->breakdownBy('products.product_name', $from, $to, 10),
                    'type' => $this->breakdownBy('products.oil_type', $from, $to),
                    'brand' => $this->breakdownBy('products.brand', $from, $to),
                ],
                'customers' => $this->customers($from, $to),
                'payment_mix' => $this->paymentMix($from, $to),
                'order_status' => $this->orderStatus($from, $to),
                'receivables' => $this->receivables($from, $to),
            ],
        ]);
    }

    private function activeBetween(Carbon $from, Carbon $to)
    {
        return Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sale_date', [$from, $to]);
    }

    private function headline(array $range): array
    {
        $now = $this->activeBetween($range['from'], $range['to'])
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected')
            ->first();

        $was = $range['previous_from']
            ? $this->activeBetween($range['previous_from'], $range['previous_to'])
                ->selectRaw('COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected')
                ->first()
            : null;

        $cancelled = Sale::where('order_status', Sale::STATUS_CANCELLED)
            ->whereBetween('sale_date', [$range['from'], $range['to']])
            ->count();

        $averageNow = $now->orders ? (float) $now->booked / $now->orders : 0.0;
        $averageWas = $was && $was->orders ? (float) $was->booked / $was->orders : null;

        return [
            'collected' => $this->withDelta((float) $now->collected, $was ? (float) $was->collected : null),
            'booked' => $this->withDelta((float) $now->booked, $was ? (float) $was->booked : null),
            'orders' => $this->withDelta((int) $now->orders, $was ? (int) $was->orders : null),
            'average_order' => $this->withDelta($averageNow, $averageWas),
            'cancelled_orders' => $cancelled,
            'cancellation_rate' => $now->orders + $cancelled > 0
                ? round($cancelled / ($now->orders + $cancelled) * 100, 1)
                : 0,
            // What share of everything ordered has actually been paid for.
            'collection_rate' => (float) $now->booked > 0
                ? round((float) $now->collected / (float) $now->booked * 100, 1)
                : 0,
        ];
    }

    /** A value beside the same value last period, and the move between them. */
    private function withDelta(float|int $current, float|int|null $previous): array
    {
        if ($previous === null) {
            return [
                'value' => $current,
                'previous' => null,
                'delta_percent' => null,
                'direction' => 'flat',
            ];
        }

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

    /** Orders taken against money received, cut to the window's own bucket. */
    private function series(array $range): array
    {
        $format = $this->bucketSqlFormat($range['bucket']);

        $rows = $this->activeBetween($range['from'], $range['to'])
            ->selectRaw("DATE_FORMAT(sale_date, '{$format}') period, COUNT(*) orders, COALESCE(SUM(total_amount),0) booked, COALESCE(SUM(paid_amount),0) collected")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        return collect($this->bucketPeriods($range))
            ->map(function (array $period) use ($rows) {
                $row = $rows->get($period['key']);

                return [
                    'period' => $period['key'],
                    'label' => $period['label'],
                    'full_label' => $period['full_label'],
                    'orders' => (int) ($row->orders ?? 0),
                    'booked' => round((float) ($row->booked ?? 0), 2),
                    'collected' => round((float) ($row->collected ?? 0), 2),
                ];
            })
            ->all();
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

    /**
     * Where the orders placed in this period have got to.
     *
     * This lived on the dashboard, where it competed with the list of jobs
     * that actually need doing. It reads better here: on the dashboard it
     * was a live count of everything ever ordered, and here it is the
     * outcome of one period's trade, which is a question analytics answers.
     */
    private function orderStatus(Carbon $from, Carbon $to): array
    {
        $placed = fn () => Sale::whereBetween('sale_date', [$from, $to]);
        $active = fn () => $placed()->where('order_status', Sale::STATUS_ACTIVE);

        return [
            ['key' => 'to_pay', 'label' => 'Awaiting payment', 'tone' => 'amber',
                'count' => $active()->whereIn('payment_status', ['unpaid', 'partial'])->count()],
            ['key' => 'review', 'label' => 'Payment to review', 'tone' => 'sky',
                'count' => $active()->where('payment_status', 'processing')->count()],
            ['key' => 'to_receive', 'label' => 'Out for delivery', 'tone' => 'violet',
                'count' => $active()->where('payment_status', 'paid')->where('delivery_status', '!=', 'delivered')->count()],
            ['key' => 'delivered', 'label' => 'Delivered', 'tone' => 'emerald',
                'count' => $active()->where('delivery_status', 'delivered')->count()],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'tone' => 'slate',
                'count' => $placed()->where('order_status', Sale::STATUS_CANCELLED)->count()],
        ];
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

    /**
     * Sales split along one dimension - the product itself, the kind of oil,
     * or the brand - so the same trade can be read at three depths without
     * three different queries.
     *
     * Products are capped, because there is no reading a list of every line
     * ever sold. The cap is reported so the panel can say what it is showing.
     */
    private function breakdownBy(string $column, Carbon $from, Carbon $to, ?int $limit = null): array
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sales.sale_id', '=', 'sale_items.sale_id')
            ->join('products', 'products.product_id', '=', 'sale_items.product_id')
            ->where('sales.order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->groupBy(DB::raw($column))
            ->selectRaw("{$column} AS label, SUM(sale_items.subtotal) revenue, SUM(sale_items.quantity) units")
            ->orderByDesc('revenue');

        // How many distinct values exist, counted without fetching them, so
        // the panel can say "10 of 42" rather than implying it shows them all.
        $available = DB::table('sale_items')
            ->join('sales', 'sales.sale_id', '=', 'sale_items.sale_id')
            ->join('products', 'products.product_id', '=', 'sale_items.product_id')
            ->where('sales.order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->distinct()
            ->count(DB::raw($column));

        if ($limit) {
            $query->limit($limit);
        }

        $rows = $query->get();

        // Shares are of everything sold, not of the rows that fit on screen,
        // so a capped list still adds up to less than 100% and says why.
        $total = (float) DB::table('sale_items')
            ->join('sales', 'sales.sale_id', '=', 'sale_items.sale_id')
            ->where('sales.order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sales.sale_date', [$from, $to])
            ->sum('sale_items.subtotal');

        return [
            'rows' => $rows->map(fn ($row) => [
                'label' => $row->label,
                'revenue' => round((float) $row->revenue, 2),
                'units' => (int) $row->units,
                'share' => $total > 0 ? round((float) $row->revenue / $total * 100, 1) : 0,
            ])->all(),
            'shown' => $rows->count(),
            'available' => $available,
        ];
    }

    /**
     * Who bought in this period, and whether they had bought before.
     *
     * A customer is new the first time they order and returning every time
     * after, so the two add up to everyone who bought in the window. Counted
     * against the period rather than the whole history, because "how many
     * customers" only means something inside a span of time.
     */
    private function customers(Carbon $from, Carbon $to): array
    {
        $rows = $this->activeBetween($from, $to)
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) orders')
            ->groupBy('user_id')
            ->get();

        $count = $rows->count();
        $orders = (int) $rows->sum('orders');

        $returning = $count > 0
            ? Sale::where('order_status', Sale::STATUS_ACTIVE)
                ->whereIn('user_id', $rows->pluck('user_id'))
                ->where('sale_date', '<', $from)
                ->distinct()
                ->count('user_id')
            : 0;

        return [
            'customers' => $count,
            'new' => $count - $returning,
            'returning' => $returning,
            'returning_rate' => $count > 0 ? round($returning / $count * 100, 1) : 0,
            'orders' => $orders,
            'orders_per_customer' => $count > 0 ? round($orders / $count, 1) : 0,
        ];
    }

    /** What is still owed on the orders placed in this period. */
    private function receivables(Carbon $from, Carbon $to): array
    {
        $row = $this->activeBetween($from, $to)
            ->where('balance_due', '>', 0)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(balance_due),0) outstanding')
            ->first();

        $awaitingReview = DB::table('payment_requests')
            ->join('sales', 'sales.sale_id', '=', 'payment_requests.sale_id')
            ->where('payment_requests.status', 'processing')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->count();

        return [
            'outstanding' => round((float) $row->outstanding, 2),
            'orders' => (int) $row->orders,
            'awaiting_review' => $awaitingReview,
        ];
    }
}
