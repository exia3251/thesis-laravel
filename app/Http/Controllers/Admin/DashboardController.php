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

        return response()->json([
            'success' => true,
            'data' => [
                'range' => $this->rangePayload($range),
                'cards' => $this->cards($range),
                'collections' => $this->collections($range),
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

        return [
            $this->card('collected', 'Collected', (float) $now->collected, $was ? (float) $was->collected : null, 'money', 'peso'),
            $this->card('orders', 'Orders', (int) $now->orders, $was ? (int) $was->orders : null, 'count', 'cart'),
            $this->card('average_order', 'Average order', $average, $averageWas, 'money', 'receipt'),
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
    private function actions(): array
    {
        $stock = Product::with('inventory')->get()->map(fn (Product $product) => [
            'quantity' => (int) ($product->inventory->quantity ?? 0),
            'reorder' => (int) $product->reorder_level,
        ]);

        $active = fn () => Sale::where('order_status', Sale::STATUS_ACTIVE);

        return [
            $this->action(
                'out_of_stock',
                $stock->filter(fn ($row) => $row['quantity'] <= 0)->count(),
                'product out of stock', 'products out of stock',
                'red', '/admin/inventory?stock=out_of_stock'
            ),
            $this->action(
                'low_stock',
                $stock->filter(fn ($row) => $row['quantity'] > 0 && $row['quantity'] <= $row['reorder'])->count(),
                'product running low', 'products running low',
                'amber', '/admin/inventory?stock=low_stock'
            ),
            $this->action(
                'awaiting_payment',
                $active()->whereIn('payment_status', ['unpaid', 'partial'])->count(),
                'order awaiting payment', 'orders awaiting payment',
                'amber', '/admin/sales?focus=owing'
            ),
            $this->action(
                // Counted on the order rather than on the receipt attached to
                // it, so this number matches the rows the link lands on.
                'to_verify',
                $active()->where('payment_status', 'processing')->count(),
                'payment to verify', 'payments to verify',
                'sky', '/admin/sales?focus=processing'
            ),
            $this->action(
                'to_deliver',
                $active()->where('payment_status', 'paid')->where('delivery_status', '!=', 'delivered')->count(),
                'paid order not yet delivered', 'paid orders not yet delivered',
                'violet', '/admin/sales?focus=undelivered'
            ),
            $this->action(
                // Money owed back on a cancelled order. There is no separate
                // returns queue in this system; a return that is agreed is
                // settled as a refund, and this is that queue.
                'refunds',
                Sale::where('refund_status', Sale::REFUND_PENDING)->count(),
                'refund to process', 'refunds to process',
                'emerald', '/admin/sales?focus=refunds'
            ),
        ];
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
