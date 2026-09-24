<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The analytics screen, read over the same spans as the dashboard.
 *
 * The screen's promise is that every figure on it describes the selected
 * period and nothing else, so most of these tests put trade on both sides of
 * a boundary and check that only one side is counted.
 */
class AnalyticsRangeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function customer(string $email): User
    {
        return User::create([
            'email' => $email,
            'password' => 'secret12345',
            'full_name' => 'Customer ' . $email,
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    private function sale(User $user, string $date, float $total = 1000, float $paid = 1000): Sale
    {
        return Sale::create([
            'user_id' => $user->user_id,
            'order_no' => 'ORD-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => $user->full_name,
            'contact_phone' => '09171234567',
            'delivery_address' => '1 Mabini Street, Batangas City',
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance_due' => $total - $paid,
            'payment_method' => 'cash',
            'payment_plan' => 'cod',
            'payment_status' => $paid >= $total ? 'paid' : 'partial',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
            'sale_date' => $date,
        ]);
    }

    private function item(Sale $sale, int $quantity, float $subtotal): void
    {
        // The test database starts empty, so the catalogue this sale draws on
        // has to exist before the line can point at it.
        $product = Product::firstOrCreate(
            ['product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN'],
            [
                'brand' => 'CANROYAL',
                'product_line' => 'canroyal-5w30',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W-30',
                'unit' => '1 Liter',
                'price' => 420,
                'reorder_level' => 10,
            ]
        );

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => $quantity,
            'unit_price' => $subtotal / $quantity,
            'subtotal' => $subtotal,
        ]);
    }

    private function data(User $admin, array $query = [])
    {
        return $this->actingAs($admin, 'staff')->getJson('/admin-api/analytics?' . http_build_query($query));
    }

    #[Test]
    public function it_reads_the_same_spans_as_the_dashboard(): void
    {
        $admin = $this->admin();

        $range = $this->data($admin, ['range' => '6m'])->assertOk()->json('data.range');

        $this->assertSame(now()->subMonthsNoOverflow(5)->startOfMonth()->toDateString(), $range['from']);
        $this->assertSame('month', $range['bucket']);
    }

    #[Test]
    public function it_defaults_to_twelve_months(): void
    {
        $admin = $this->admin();

        $this->assertSame('12m', $this->data($admin)->assertOk()->json('data.range.key'));
    }

    #[Test]
    public function a_short_window_is_cut_by_day_here_too(): void
    {
        $admin = $this->admin();

        $data = $this->data($admin, ['range' => '7d'])->assertOk()->json('data');

        $this->assertSame('day', $data['range']['bucket']);
        $this->assertCount(7, $data['series']);
    }

    #[Test]
    public function a_custom_window_cannot_end_before_it_starts(): void
    {
        $admin = $this->admin();

        $this->data($admin, ['range' => 'custom', 'from' => '2026-05-01', 'to' => '2026-04-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    #[Test]
    public function the_money_owed_is_owed_on_this_period_s_orders(): void
    {
        $admin = $this->admin();
        $shopper = $this->customer('one@raney.test');

        // Half paid inside the window, half paid well outside it.
        $this->sale($shopper, '2026-03-10 09:00:00', 1000, 400);
        $this->sale($shopper, '2025-01-10 09:00:00', 5000, 100);

        $receivables = $this->data($admin, ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertOk()
            ->json('data.receivables');

        $this->assertEqualsWithDelta(600, $receivables['outstanding'], 0.001, 'The older order is outside the window.');
        $this->assertSame(1, $receivables['orders']);
    }

    #[Test]
    public function customers_are_counted_inside_the_window(): void
    {
        $admin = $this->admin();
        $alice = $this->customer('alice@raney.test');
        $bob = $this->customer('bob@raney.test');
        $carol = $this->customer('carol@raney.test');

        // Alice bought before and during; Bob and Carol only during.
        $this->sale($alice, '2025-11-01 09:00:00');
        $this->sale($alice, '2026-03-05 09:00:00');
        $this->sale($alice, '2026-03-20 09:00:00');
        $this->sale($bob, '2026-03-08 09:00:00');
        $this->sale($carol, '2026-03-09 09:00:00');

        $customers = $this->data($admin, ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertOk()
            ->json('data.customers');

        $this->assertSame(3, $customers['customers']);
        $this->assertSame(1, $customers['returning'], 'Only Alice had bought before March.');
        $this->assertSame(2, $customers['new']);
        $this->assertEqualsWithDelta(33.3, $customers['returning_rate'], 0.05);
        $this->assertEqualsWithDelta(1.3, $customers['orders_per_customer'], 0.05, 'Four orders between three of them.');
    }

    #[Test]
    public function new_and_returning_account_for_everybody(): void
    {
        $admin = $this->admin();
        $this->sale($this->customer('a@raney.test'), '2026-03-05 09:00:00');
        $this->sale($this->customer('b@raney.test'), '2026-03-06 09:00:00');

        $customers = $this->data($admin, ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertOk()
            ->json('data.customers');

        $this->assertSame(
            $customers['customers'],
            $customers['new'] + $customers['returning'],
            'Everyone who bought is either new or returning, never both and never neither.'
        );
    }

    #[Test]
    public function the_breakdown_carries_units_sold_and_says_what_it_is_hiding(): void
    {
        $admin = $this->admin();
        $shopper = $this->customer('buyer@raney.test');

        $sale = $this->sale($shopper, '2026-03-10 09:00:00', 2000, 2000);
        $this->item($sale, 7, 2000);

        $breakdown = $this->data($admin, ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertOk()
            ->json('data.breakdown');

        $this->assertArrayHasKey('rows', $breakdown['product']);
        $this->assertArrayHasKey('available', $breakdown['product']);
        $this->assertSame(7, $breakdown['product']['rows'][0]['units']);
        $this->assertSame($breakdown['product']['shown'], count($breakdown['product']['rows']));
    }

    #[Test]
    public function sales_outside_the_window_are_left_out_of_the_breakdown(): void
    {
        $admin = $this->admin();
        $shopper = $this->customer('buyer@raney.test');

        $inside = $this->sale($shopper, '2026-03-10 09:00:00', 2000, 2000);
        $this->item($inside, 7, 2000);

        $outside = $this->sale($shopper, '2026-06-10 09:00:00', 9000, 9000);
        $this->item($outside, 40, 9000);

        $breakdown = $this->data($admin, ['range' => 'custom', 'from' => '2026-03-01', 'to' => '2026-03-31'])
            ->assertOk()
            ->json('data.breakdown.product');

        $this->assertSame(7, collect($breakdown['rows'])->sum('units'));
    }

    #[Test]
    public function all_time_drops_the_comparison_here_as_well(): void
    {
        $admin = $this->admin();
        $this->sale($this->customer('a@raney.test'), now()->subDays(3)->toDateTimeString());

        $headline = $this->data($admin, ['range' => 'all'])->assertOk()->json('data.headline');

        $this->assertNull($headline['collected']['delta_percent']);
        $this->assertNull($headline['booked']['delta_percent']);
    }

    #[Test]
    public function the_stock_and_concentration_panels_are_gone(): void
    {
        $admin = $this->admin();

        $data = $this->data($admin)->assertOk()->json('data');

        $this->assertArrayNotHasKey('inventory_health', $data);
        $this->assertArrayNotHasKey('concentration', $data);
    }
}
