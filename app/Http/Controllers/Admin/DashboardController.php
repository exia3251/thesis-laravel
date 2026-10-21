<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesReportRange;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * The operations view: what happened recently, and what needs doing now.
 *
 * Deliberately narrower than the analytics screen. This one answers "is
 * anything on fire today", so every figure is shown against the same span
 * immediately before it, and the action list names the job rather than
 * counting it.
 */
class DashboardController extends Controller
{
    use ResolvesReportRange;

    public function index()
    {
        return view('admin.dashboard');
    }

    public function getStats(Request $request)
    {
        $request->validate($this->rangeRules(), $this->rangeMessages());

        $range = $this->resolveRange($request);

        // The collections chart is money from end to end, so it goes to the
        // people money is theirs to look at. An empty array rather than a
        // missing key, so the page draws its own "nothing here" instead of
        // tripping over an absence.
        $collections = $request->user()?->canSeeMoneyFigures() ? $this->collections($range) : [];

        return response()->json([
            'success' => true,
            'data' => [
                'range' => $this->rangePayload($range),
                'cards' => $this->cards($range),
                'collections' => $collections,
                'actions' => $this->actions(),
            ],
        ]);
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

        // What a typical order is worth. Booked rather than collected, because
        // an order half paid for is still an order of its full size.
        $average = $now->orders > 0 ? (float) $now->booked / (int) $now->orders : 0.0;
        $averageWas = $was && $was->orders > 0 ? (float) $was->booked / (int) $was->orders : null;

        /*
         * Each figure is tagged with who it is for, then the ones this
         * reader is not for are dropped. Filtered here rather than hidden in
         * the page: a number a role may not see should not travel to their
         * browser at all, or the rule is decoration.
         */
        $user = auth()->user();
        $money = (bool) $user?->canSeeMoneyFigures();
        $stock = (bool) $user?->canSeeStockFigures();

        return array_values(array_filter([
            $money ? $this->card('collected', 'Collected', (float) $now->collected, $was ? (float) $was->collected : null, 'money', 'peso') : null,

            // The count of orders is neither money nor stock: it is how busy
            // the shop has been, which everybody working in it may know.
            $this->card('orders', 'Orders', (int) $now->orders, $was ? (int) $was->orders : null, 'count', 'cart'),

            $money ? $this->card('average_order', 'Average order', $average, $averageWas, 'money', 'receipt') : null,

            $stock ? [
                'key' => 'inventory_value',
                'label' => 'Stock on hand',
                'value' => round($inventoryValue, 2),
                'format' => 'money',
                'icon' => 'box',
                'note' => Product::count() . ' products',
                'delta_percent' => null,
                'direction' => 'flat',
            ] : null,

            $money ? [
                'key' => 'outstanding',
                'label' => 'Owed to you',
                'value' => round($outstanding, 2),
                'format' => 'money',
                'icon' => 'clock',
                'note' => $outstandingOrders . ' orders unsettled',
                'delta_percent' => null,
                'direction' => 'flat',
            ] : null,
        ]));
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

    /** Collections over the chosen window, cut to the chosen bucket. */
    private function collections(array $range): array
    {
        $format = $this->bucketSqlFormat($range['bucket']);

        $rows = $this->activeBetween($range['from'], $range['to'])
            ->selectRaw("DATE_FORMAT(sale_date, '{$format}') period, COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        return collect($this->bucketPeriods($range))
            ->map(function (array $period) use ($rows) {
                $row = $rows->get($period['key']);

                return [
                    'label' => $period['label'],
                    'full_label' => $period['full_label'],
                    'orders' => (int) ($row->orders ?? 0),
                    'collected' => round((float) ($row->collected ?? 0), 2),
                ];
            })
            ->all();
    }

    /**
     * The work waiting on somebody here, and where to go and do it.
     *
     * Deliberately not tied to the period above: a customer who has not paid
     * has not paid whichever span the chart is showing. Every row carries the
     * address of the page that can clear it, filtered to the same set it
     * counted, so the number and the screen behind it always agree.
     */
    /**
     * The jobs waiting on somebody here, narrowed to the ones waiting on
     * this somebody.
     *
     * A queue nobody in this role can clear is not a notification, it is
     * noise -- and worse than noise, because it tells an accountant the
     * shelf is empty and gives them nothing to do about it. Stock queues go
     * to the people who hold stock; money queues go to the people who handle
     * money. The administrator holds both, so sees both.
     *
     * The counts behind a queue this reader does not get are not computed at
     * all, which keeps the rule honest: nothing is sent and then hidden.
     */
    private function actions(): array
    {
        $user = auth()->user();
        $seesStock = (bool) $user?->canSeeStockFigures();
        $seesMoney = (bool) $user?->canSeeMoneyFigures();

        $stock = $seesStock
            ? Product::with('inventory')->get()->map(fn (Product $product) => [
                'quantity' => (int) ($product->inventory->quantity ?? 0),
                'reorder' => (int) $product->reorder_level,
            ])
            : collect();

        $active = fn () => Sale::where('order_status', Sale::STATUS_ACTIVE);

        return array_values(array_filter([
            ! $seesStock ? null : $this->action(
                'out_of_stock',
                $stock->filter(fn ($row) => $row['quantity'] <= 0)->count(),
                'product out of stock', 'products out of stock',
                'red', '/admin/inventory?stock=out_of_stock'
            ),
            ! $seesStock ? null : $this->action(
                'low_stock',
                $stock->filter(fn ($row) => $row['quantity'] > 0 && $row['quantity'] <= $row['reorder'])->count(),
                'product running low', 'products running low',
                'amber', '/admin/inventory?stock=low_stock'
            ),
            ! $seesMoney ? null : $this->action(
                'awaiting_payment',
                $active()->whereIn('payment_status', ['unpaid', 'partial'])->count(),
                'order awaiting payment', 'orders awaiting payment',
                'amber', '/admin/sales?focus=owing'
            ),
            ! $seesMoney ? null : $this->action(
                // Counted on the order rather than on the receipt attached to
                // it, so this number matches the rows the link lands on.
                'to_verify',
                $active()->where('payment_status', 'processing')->count(),
                'payment to verify', 'payments to verify',
                'sky', '/admin/sales?focus=processing'
            ),
            ! $seesStock ? null : $this->action(
                'to_deliver',
                $active()->where('payment_status', 'paid')->where('delivery_status', '!=', 'delivered')->count(),
                'paid order not yet delivered', 'paid orders not yet delivered',
                'violet', '/admin/sales?focus=undelivered'
            ),
            ! $seesMoney ? null : $this->action(
                // Money owed back on a cancelled order. There is no separate
                // returns queue in this system; a return that is agreed is
                // settled as a refund, and this is that queue.
                'refunds',
                Sale::where('refund_status', Sale::REFUND_PENDING)->count(),
                'refund to process', 'refunds to process',
                'emerald', '/admin/sales?focus=refunds'
            ),
        ]));
    }

    private function action(string $key, int $count, string $singular, string $plural, string $tone, string $href): array
    {
        return [
            'key' => $key,
            'count' => $count,
            'label' => $count === 1 ? $singular : $plural,
            'tone' => $tone,
            'href' => $href,
        ];
    }
}
