<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The operations view: what happened recently, and what needs doing now.
 *
 * Deliberately narrower than the analytics screen. This one answers "is
 * anything on fire today", so every figure is shown against the same span
 * immediately before it, and the attention list names specific products
 * rather than counting them.
 */
class DashboardController extends Controller
{
    /**
     * The spans the dashboard can be read over.
     *
     * Month-length spans start at the first of the month so the bars are
     * whole months rather than a rolling window cut through the middle of
     * two of them. Day-length spans end today, because that is the question
     * being asked of them.
     */
    private const RANGES = [
        'all' => ['label' => 'All time'],
        '12m' => ['label' => 'Last 12 months', 'months' => 12],
        '6m' => ['label' => 'Last 6 months', 'months' => 6],
        '3m' => ['label' => 'Last 3 months', 'months' => 3],
        '30d' => ['label' => 'Last 30 days', 'days' => 30],
        '7d' => ['label' => 'Last 7 days', 'days' => 7],
        'custom' => ['label' => 'Custom range'],
    ];

    public function index()
    {
        return view('admin.dashboard');
    }

    public function getStats(Request $request)
    {
        $request->validate([
            'range' => ['nullable', Rule::in(array_keys(self::RANGES))],
            'from' => ['required_if:range,custom', 'nullable', 'date'],
            'to' => ['required_if:range,custom', 'nullable', 'date', 'after_or_equal:from'],
        ], [
            'from.required_if' => 'Choose a start date.',
            'to.required_if' => 'Choose an end date.',
            'to.after_or_equal' => 'The end date cannot come before the start date.',
        ]);

        $range = $this->resolveRange($request);

        return response()->json([
            'success' => true,
            'data' => [
                'range' => [
                    'key' => $range['key'],
                    'label' => $range['label'],
                    'from' => $range['from']->toDateString(),
                    'to' => $range['to']->toDateString(),
                    'bucket' => $range['bucket'],
                    'description' => $this->describe($range),
                ],
                'cards' => $this->cards($range),
                'collections' => $this->collections($range),
                'order_status' => $this->orderStatus(),
                'attention' => $this->attention(),
            ],
        ]);
    }

    /**
     * The window being asked for, and the one immediately before it.
     *
     * "All time" has nothing before it, so it carries no previous window and
     * the cards drop their comparison rather than measure against zero.
     */
    private function resolveRange(Request $request): array
    {
        $key = $request->get('range', '30d');

        if (! isset(self::RANGES[$key])) {
            $key = '30d';
        }

        $to = now()->endOfDay();

        if ($key === 'custom') {
            $from = Carbon::parse($request->get('from'))->startOfDay();
            $to = Carbon::parse($request->get('to'))->endOfDay();

            // Nothing is recorded ahead of now, so a window running past
            // today would only pad the chart with empty buckets.
            if ($to->isFuture()) {
                $to = now()->endOfDay();
            }

            $length = $this->daySpan($from, $to);
            $previousTo = $from->copy()->subSecond();

            return $this->range($key, $from, $to, $previousTo->copy()->subDays($length - 1)->startOfDay(), $previousTo);
        }

        if ($key === 'all') {
            $earliest = Sale::min('sale_date');
            $from = $earliest ? Carbon::parse($earliest)->startOfDay() : now()->startOfDay();

            return $this->range($key, $from, $to, null, null);
        }

        $spec = self::RANGES[$key];

        if (isset($spec['months'])) {
            $from = now()->subMonthsNoOverflow($spec['months'] - 1)->startOfMonth();
            $previousFrom = $from->copy()->subMonthsNoOverflow($spec['months']);
        } else {
            $from = now()->subDays($spec['days'] - 1)->startOfDay();
            $previousFrom = $from->copy()->subDays($spec['days']);
        }

        return $this->range($key, $from, $to, $previousFrom, $from->copy()->subSecond());
    }

    private function range(string $key, Carbon $from, Carbon $to, ?Carbon $previousFrom, ?Carbon $previousTo): array
    {
        return [
            'key' => $key,
            'label' => self::RANGES[$key]['label'],
            'from' => $from,
            'to' => $to,
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
            'bucket' => $this->bucketFor($from, $to),
        ];
    }

    /**
     * How finely to cut the window up.
     *
     * A month of trade reads by day; a year of it does not, and three years
     * of months is a wall of bars nobody can count.
     */
    private function bucketFor(Carbon $from, Carbon $to): string
    {
        $days = $this->daySpan($from, $to);

        if ($days <= 31) {
            return 'day';
        }

        return $days <= 1100 ? 'month' : 'year';
    }

    /**
     * Whole days between two moments, counted inclusively.
     *
     * These windows run from the start of one day to the end of another, and
     * Carbon measures the difference in fractions of a day, so a calendar
     * month came back as 31.99 days and was cut by month instead of by day.
     */
    private function daySpan(Carbon $from, Carbon $to): int
    {
        return (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
    }

    private function describe(array $range): string
    {
        $by = ['day' => 'by day', 'month' => 'by month', 'year' => 'by year'][$range['bucket']];

        return $range['from']->format('j M Y') . ' to ' . $range['to']->format('j M Y') . ', ' . $by;
    }

    private function activeBetween(Carbon $from, Carbon $to)
    {
        return Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sale_date', [$from, $to]);
    }

    private function cards(array $range): array
    {
        $now = $this->activeBetween($range['from'], $range['to'])
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected, COALESCE(SUM(total_amount),0) booked')
            ->first();

        $was = $range['previous_from']
            ? $this->activeBetween($range['previous_from'], $range['previous_to'])
                ->selectRaw('COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected, COALESCE(SUM(total_amount),0) booked')
                ->first()
            : null;

        $inventoryValue = (float) (Product::join('inventory', 'products.product_id', '=', 'inventory.product_id')
            ->selectRaw('SUM(products.price * inventory.quantity) total')
            ->value('total') ?? 0);

        $outstanding = (float) Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->where('balance_due', '>', 0)
            ->sum('balance_due');

        $outstandingOrders = Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->where('balance_due', '>', 0)
            ->count();

        return [
            $this->card('collected', 'Collected', (float) $now->collected, $was ? (float) $was->collected : null, 'money', 'peso'),
            $this->card('orders', 'Orders', (int) $now->orders, $was ? (int) $was->orders : null, 'count', 'cart'),
            [
                'key' => 'inventory_value',
                'label' => 'Stock on hand',
                'value' => round($inventoryValue, 2),
                'format' => 'money',
                'icon' => 'box',
                'note' => Product::count() . ' products',
                'delta_percent' => null,
                'direction' => 'flat',
            ],
            [
                'key' => 'outstanding',
                'label' => 'Owed to you',
                'value' => round($outstanding, 2),
                'format' => 'money',
                'icon' => 'clock',
                'note' => $outstandingOrders . ' orders unsettled',
                'delta_percent' => null,
                'direction' => 'flat',
            ],
        ];
    }

    private function card(string $key, string $label, float|int $current, float|int|null $previous, string $format, string $icon): array
    {
        // Nothing to compare against: "all time" has no period before it.
        if ($previous === null) {
            return [
                'key' => $key,
                'label' => $label,
                'value' => $format === 'money' ? round($current, 2) : $current,
                'format' => $format,
                'icon' => $icon,
                'note' => 'across the whole period',
                'delta_percent' => null,
                'direction' => 'flat',
            ];
        }

        $change = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        return [
            'key' => $key,
            'label' => $label,
            'value' => $format === 'money' ? round($current, 2) : $current,
            'format' => $format,
            'icon' => $icon,
            'note' => 'vs previous period',
            'delta_percent' => $change,
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * Collections over the chosen window, cut to the chosen bucket.
     *
     * Empty buckets are filled in rather than skipped, so a quiet month
     * shows as a gap in the trade instead of vanishing from the chart.
     */
    private function collections(array $range): array
    {
        $shapes = [
            'day' => ['%Y-%m-%d', 'j M', 'j F Y'],
            'month' => ['%Y-%m', 'M', 'F Y'],
            'year' => ['%Y', 'Y', 'Y'],
        ];

        [$sqlFormat, $shortFormat, $longFormat] = $shapes[$range['bucket']];

        $rows = Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sale_date', [$range['from'], $range['to']])
            ->selectRaw("DATE_FORMAT(sale_date, '{$sqlFormat}') period, COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $cursor = match ($range['bucket']) {
            'day' => $range['from']->copy()->startOfDay(),
            'month' => $range['from']->copy()->startOfMonth(),
            'year' => $range['from']->copy()->startOfYear(),
        };

        $series = [];

        // Guarded against a range so long it would be unreadable anyway;
        // the bucket widens before this bites, so it is a backstop only.
        while ($cursor->lte($range['to']) && count($series) < 400) {
            $row = $rows->get($cursor->format(str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $sqlFormat)));

            $series[] = [
                'label' => $cursor->format($shortFormat),
                'full_label' => $cursor->format($longFormat),
                'orders' => (int) ($row->orders ?? 0),
                'collected' => round((float) ($row->collected ?? 0), 2),
            ];

            match ($range['bucket']) {
                'day' => $cursor->addDay(),
                'month' => $cursor->addMonthNoOverflow(),
                'year' => $cursor->addYear(),
            };
        }

        return $series;
    }

    /** Where every live order currently sits. */
    private function orderStatus(): array
    {
        $active = Sale::where('order_status', Sale::STATUS_ACTIVE);

        $toPay = (clone $active)->where('payment_status', '!=', 'paid')->count();
        $awaitingReview = DB::table('payment_requests')->where('status', 'processing')->count();
        $toReceive = (clone $active)->where('payment_status', 'paid')->where('delivery_status', '!=', 'delivered')->count();
        $delivered = (clone $active)->where('delivery_status', 'delivered')->count();
        $cancelled = Sale::where('order_status', Sale::STATUS_CANCELLED)->count();

        return [
            ['key' => 'to_pay', 'label' => 'Awaiting payment', 'count' => $toPay, 'tone' => 'amber'],
            ['key' => 'review', 'label' => 'Payment to review', 'count' => $awaitingReview, 'tone' => 'sky'],
            ['key' => 'to_receive', 'label' => 'Out for delivery', 'count' => $toReceive, 'tone' => 'violet'],
            ['key' => 'delivered', 'label' => 'Delivered', 'count' => $delivered, 'tone' => 'emerald'],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'count' => $cancelled, 'tone' => 'slate'],
        ];
    }

    /**
     * Named products rather than a count, because "7 out of stock" is not
     * something anyone can act on without opening another page.
     */
    private function attention(): array
    {
        $rows = Product::with('inventory')->get()->map(function ($product) {
            $quantity = (int) ($product->inventory->quantity ?? 0);
            $reorder = (int) $product->reorder_level;

            return [
                'product_id' => $product->product_id,
                'name' => $product->product_name,
                // Without the pack size the three Patrol 5W30 rows are the
                // same sentence three times over.
                'unit' => $product->unit,
                'brand' => $product->brand,
                'quantity' => $quantity,
                'reorder_level' => $reorder,
                'status' => $quantity <= 0 ? 'out' : ($quantity <= $reorder ? 'low' : 'ok'),
            ];
        });

        return [
            'out_of_stock' => $rows->where('status', 'out')->sortBy('name')->values()->take(5)->all(),
            'low_stock' => $rows->where('status', 'low')->sortBy('quantity')->values()->take(5)->all(),
            'out_of_stock_total' => $rows->where('status', 'out')->count(),
            'low_stock_total' => $rows->where('status', 'low')->count(),
        ];
    }
}
