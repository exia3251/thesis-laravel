<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function index()
    {
        return view('admin.dashboard');
    }

    public function getStats(Request $request)
    {
        $request->validate(['days' => 'nullable|integer|min:7|max:365']);

        $days = (int) $request->get('days', 30);
        $to = now()->endOfDay();
        $from = now()->copy()->subDays($days - 1)->startOfDay();
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        return response()->json([
            'success' => true,
            'data' => [
                'range' => [
                    'days' => $days,
                    'label' => 'Last ' . $days . ' days',
                ],
                'cards' => $this->cards($from, $to, $previousFrom, $previousTo),
                'monthly_revenue' => $this->monthlyRevenue(),
                'order_status' => $this->orderStatus(),
                'attention' => $this->attention(),
            ],
        ]);
    }

    private function activeBetween(Carbon $from, Carbon $to)
    {
        return Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->whereBetween('sale_date', [$from, $to]);
    }

    private function cards(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $now = $this->activeBetween($from, $to)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected, COALESCE(SUM(total_amount),0) booked')
            ->first();

        $was = $this->activeBetween($previousFrom, $previousTo)
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected, COALESCE(SUM(total_amount),0) booked')
            ->first();

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
            $this->card('collected', 'Collected', (float) $now->collected, (float) $was->collected, 'money', 'peso'),
            $this->card('orders', 'Orders', (int) $now->orders, (int) $was->orders, 'count', 'cart'),
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

    private function card(string $key, string $label, float|int $current, float|int $previous, string $format, string $icon): array
    {
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

    /** Six months of collections, for the bar list. */
    private function monthlyRevenue(): array
    {
        $from = now()->copy()->subMonths(5)->startOfMonth();

        $rows = Sale::where('order_status', Sale::STATUS_ACTIVE)
            ->where('sale_date', '>=', $from)
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') period, COUNT(*) orders, COALESCE(SUM(paid_amount),0) collected")
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        $series = [];

        for ($month = $from->copy(); $month->lte(now()); $month->addMonth()) {
            $row = $rows->get($month->format('Y-m'));

            $series[] = [
                'label' => $month->format('M'),
                'full_label' => $month->format('F Y'),
                'orders' => (int) ($row->orders ?? 0),
                'collected' => round((float) ($row->collected ?? 0), 2),
            ];
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
