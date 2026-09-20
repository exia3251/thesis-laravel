<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fabricates a year of trading history so the analytics have a shape to show.
 *
 * Run deliberately, never from DatabaseSeeder:
 *
 *     php artisan db:seed --class=DemoSalesSeeder
 *
 * Orders are written straight to sales and sale_items and do NOT move current
 * stock. They represent movements that already happened and were already
 * accounted for; decrementing today's inventory by a year of past trade would
 * drive it negative and misrepresent what is actually on the shelf.
 *
 * Every row it creates is tagged in customer_name so it can be removed again
 * with the same class, which makes it safe to re-run.
 */
class DemoSalesSeeder extends Seeder
{
    private const TAG = '[demo] ';

    public function run(): void
    {
        $products = Product::with('inventory')->get();

        if ($products->isEmpty()) {
            $this->command?->warn('No products to sell. Run ProductCatalogSeeder first.');
            return;
        }

        $this->clear();

        $buyers = $this->makeCustomers();

        if (!$buyers) {
            $this->command?->warn('No customer accounts to attribute demo orders to.');
            return;
        }

        // Trade concentrates: a handful of fleet accounts buy constantly while
        // most customers order occasionally. Weighting the draw this way is
        // what makes a Pareto curve meaningful rather than a straight line.
        $weighted = [];
        foreach (array_values($buyers) as $rank => $id) {
            foreach (range(1, (int) max(1, round(60 / ($rank + 1.6)))) as $ignored) {
                $weighted[] = $id;
            }
        }

        $created = 0;
        $start = now()->subMonths(11)->startOfMonth();

        for ($day = $start->copy(); $day->lte(now()); $day->addDay()) {
            foreach (range(1, $this->ordersFor($day)) as $ignored) {
                $this->makeOrder($day, $products, $weighted);
                $created++;
            }
        }

        $this->command?->info("Created {$created} demo orders across " . $start->format('M Y') . ' to ' . now()->format('M Y') . '.');
    }

    /** @var array<int, User> */
    private array $buyerCache = [];

    /**
     * A demo customer base with a realistic spread: a few named fleet and
     * workshop accounts that buy heavily, then a long tail of individuals.
     *
     * @return array<int, int> user ids, heaviest buyers first
     */
    private function makeCustomers(): array
    {
        $fleet = [
            'Southway Transport Co', 'Batangas Haulers Inc', 'Delgado Motor Works',
            'Pampanga Bus Lines', 'Cavite Freight Services', 'Sta Rosa Auto Care',
        ];

        $trade = [
            'Ramon Villanueva', 'Liza Bautista', 'Arnel Cruz', 'Divina Ramos',
            'Ferdinand Lim', 'Maricel Aquino', 'Noel Tolentino', 'Grace Mendoza',
            'Rodel Santiago', 'Cherry Ann Dizon', 'Bert Navarro', 'Imelda Reyes',
            'Joseph Ocampo', 'Rowena Pascual', 'Danilo Estrada', 'Marites Galang',
            'Eduardo Flores', 'Jocelyn Rivera', 'Alfredo Panganiban', 'Nenita Corpuz',
            'Ricardo Salazar', 'Melinda Torres', 'Efren Bacani', 'Lourdes Yap',
            'Wilfredo Agustin', 'Aileen Fabros', 'Nestor Mangubat', 'Teresita Uy',
            'Benjamin Carandang', 'Evelyn Sarmiento', 'Rogelio Herrera', 'Susana Bolante',
        ];

        $ids = [];

        foreach (array_merge($fleet, $trade) as $index => $name) {
            $slug = 'demo' . ($index + 1) . '@raney.test';

            $user = User::updateOrCreate(['email' => $slug], [
                'password' => 'demo12345',
                'full_name' => self::TAG . $name,
                'role' => User::ROLE_CUSTOMER,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $ids[] = $user->user_id;
        }

        return $ids;
    }

    /**
     * How busy a given day was. Trade climbs slowly across the year, dips on
     * Sundays, and lifts at the start of each month when fleet operators
     * restock, so the charts show something with a shape rather than noise.
     */
    private function ordersFor(\Carbon\Carbon $day): int
    {
        if ($day->isSunday()) {
            return random_int(0, 1);
        }

        $monthsIn = $day->diffInMonths(now()->subMonths(11)->startOfMonth());
        $base = 2 + (int) floor($monthsIn / 3);

        if ($day->day <= 5) {
            $base += 2;
        }

        return max(0, $base + random_int(-1, 2));
    }

    private function makeOrder(\Carbon\Carbon $day, $products, array $weightedBuyers): void
    {
        $buyerId = $weightedBuyers[array_rand($weightedBuyers)];
        $buyer = $this->buyerCache[$buyerId] ??= User::find($buyerId);

        $placedAt = $day->copy()->setTime(random_int(8, 17), random_int(0, 59));
        $lines = $products->random(random_int(1, 4));

        $total = 0.0;
        $rows = [];

        foreach ($lines as $product) {
            $qty = random_int(1, 6);
            $subtotal = (float) $product->price * $qty;
            $total += $subtotal;

            $rows[] = [
                'product_id' => $product->product_id,
                'quantity' => $qty,
                'unit_price' => $product->price,
                'subtotal' => $subtotal,
            ];
        }

        [$plan, $gcash] = $this->plan($total);
        $outcome = $this->outcome();

        $paid = match ($outcome) {
            'cancelled' => $gcash > 0 ? $gcash : 0.0,
            'settled'   => $total,
            'partial'   => $gcash > 0 ? $gcash : round($total * 0.5, 2),
            default     => 0.0,
        };

        $sale = Sale::create([
            'customer_name' => self::TAG . $buyer->full_name,
            'user_id' => $buyerId,
            'delivery_address' => 'Demo address, Metro Manila',
            'contact_phone' => '09' . random_int(100000000, 999999999),
            'total_amount' => $total,
            'payment_method' => $plan === Sale::PLAN_COD ? 'cash_on_delivery' : 'gcash',
            'payment_plan' => $plan,
            'gcash_amount' => $gcash,
            'payment_status' => $this->paymentStatus($paid, $total),
            'paid_amount' => $paid,
            'balance_due' => $outcome === 'cancelled' ? 0 : max($total - $paid, 0),
            'delivery_status' => $outcome === 'settled' ? 'delivered' : 'to_receive',
            'order_status' => $outcome === 'cancelled' ? Sale::STATUS_CANCELLED : Sale::STATUS_ACTIVE,
            'cancelled_at' => $outcome === 'cancelled' ? $placedAt->copy()->addDays(random_int(0, 2)) : null,
            'cancellation_reason' => $outcome === 'cancelled' ? 'Customer changed their mind' : null,
            'refund_status' => $outcome === 'cancelled' && $paid > 0 ? Sale::REFUND_DONE : Sale::REFUND_NONE,
            'refund_amount' => $outcome === 'cancelled' ? $paid : 0,
            'delivered_at' => $outcome === 'settled' ? $placedAt->copy()->addDays(random_int(1, 4)) : null,
            'sale_date' => $placedAt,
        ]);

        foreach ($rows as $row) {
            SaleItem::create($row + ['sale_id' => $sale->sale_id]);
        }

        // sale_date is set by the database default on insert, so put it back.
        Sale::query()->where('sale_id', $sale->sale_id)->update([
            'sale_date' => $placedAt,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);
    }

    /** @return array{0:string,1:float} */
    private function plan(float $total): array
    {
        $roll = random_int(1, 100);

        if ($roll <= 45) {
            return [Sale::PLAN_COD, 0.0];
        }

        if ($roll <= 75) {
            return [Sale::PLAN_GCASH_FULL, $total];
        }

        $percent = [25, 30, 40, 50][array_rand([25, 30, 40, 50])];

        return [Sale::PLAN_SPLIT, round($total * $percent / 100, 2)];
    }

    private function outcome(): string
    {
        $roll = random_int(1, 100);

        return match (true) {
            $roll <= 72 => 'settled',
            $roll <= 86 => 'partial',
            $roll <= 94 => 'open',
            default     => 'cancelled',
        };
    }

    private function paymentStatus(float $paid, float $total): string
    {
        return match (true) {
            $paid <= 0      => 'unpaid',
            $paid >= $total => 'paid',
            default         => 'partial',
        };
    }

    /** Removes anything a previous run of this seeder created. */
    public function clear(): void
    {
        $ids = Sale::where('customer_name', 'like', self::TAG . '%')->pluck('sale_id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('sale_items')->whereIn('sale_id', $ids)->delete();
        DB::table('payment_requests')->whereIn('sale_id', $ids)->delete();
        Sale::whereIn('sale_id', $ids)->delete();

        $this->command?->info("Removed {$ids->count()} demo orders from a previous run.");
    }
}
