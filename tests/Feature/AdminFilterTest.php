<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The filters the back office sorts its work with.
 *
 * Three screens describe the same work -- the dashboard counts it, Sales
 * lists it, Inventory shelves it -- and they used to do so by three separate
 * definitions. These tests hold them to one, because a count that opens a
 * screen showing something else is worse than no count at all.
 */
class AdminFilterTest extends TestCase
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

    private function sale(User $user, array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'user_id' => $user->user_id,
            'order_no' => 'ORD-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => 'Ana Reyes',
            'contact_phone' => '09171234567',
            'delivery_address' => '1 Mabini Street, Batangas City',
            'total_amount' => 1000,
            'paid_amount' => 1000,
            'balance_due' => 0,
            'payment_method' => 'cash',
            'payment_plan' => 'cod',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
            'sale_date' => now(),
        ], $overrides));
    }

    private function product(string $name, int $stock, int $reorder = 10): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'brand' => 'SOLAR',
            'product_line' => 'line-' . strtolower(str_replace(' ', '-', $name)),
            'oil_type' => 'Synthetic',
            'unit' => '1 Liter',
            'price' => 400,
            'reorder_level' => $reorder,
        ]);

        Inventory::create(['product_id' => $product->product_id, 'quantity' => $stock]);

        return $product;
    }

    #[Test]
    public function each_sales_filter_returns_only_its_own_orders(): void
    {
        $admin = $this->admin();

        $this->sale($admin, ['payment_status' => 'unpaid', 'delivery_status' => 'to_deliver']);
        $this->sale($admin, ['payment_status' => 'partial', 'delivery_status' => 'to_deliver']);
        $this->sale($admin, ['payment_status' => 'processing', 'delivery_status' => 'to_deliver']);
        $this->sale($admin, ['payment_status' => 'paid', 'delivery_status' => 'to_receive']);
        $this->sale($admin, ['payment_status' => 'paid', 'delivery_status' => 'delivered']);
        $this->sale($admin, ['order_status' => Sale::STATUS_CANCELLED, 'refund_status' => Sale::REFUND_PENDING]);

        $expected = [
            'owing' => 2,
            'processing' => 1,
            'paid' => 2,
            'to_deliver' => 1,
            'delivered' => 1,
            'refunds' => 1,
            'cancelled' => 1,
        ];

        foreach ($expected as $focus => $count) {
            $response = $this->actingAs($admin, 'staff')
                ->getJson("/admin-api/sales?focus={$focus}&per_page=100")
                ->assertOk();

            $this->assertCount($count, $response->json('data'), "The {$focus} filter returned the wrong orders.");
            $this->assertSame($count, $response->json("counts.{$focus}"), "The {$focus} count disagrees with its own rows.");
        }
    }

    #[Test]
    public function the_counts_travel_with_the_rows(): void
    {
        $admin = $this->admin();
        $this->sale($admin, ['payment_status' => 'unpaid', 'delivery_status' => 'to_deliver']);

        // Asking for one set still reports every set, so the buttons can all
        // carry a number without seven more requests.
        $counts = $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/sales?focus=owing')
            ->assertOk()
            ->json('counts');

        $this->assertSame(1, $counts['all']);
        $this->assertSame(1, $counts['owing']);
        $this->assertSame(0, $counts['delivered']);
    }

    #[Test]
    public function an_unknown_sales_filter_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/sales?focus=whatever')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['focus']);
    }

    #[Test]
    public function running_low_means_the_same_thing_on_both_screens(): void
    {
        $admin = $this->admin();

        $this->product('At the reorder level', 10, 10);   // low
        $this->product('Just above it', 11, 10);          // fine
        $this->product('Half of it', 5, 10);              // low under either rule
        $this->product('Empty', 0, 10);                   // out, not low

        $dashboard = collect(
            $this->actingAs($admin, 'staff')->getJson('/admin-api/dashboard/stats')->assertOk()->json('data.actions')
        )->keyBy('key');

        $inventory = collect(
            $this->actingAs($admin, 'staff')->getJson('/admin-api/inventory')->assertOk()->json('data')
        );

        $this->assertSame(2, $dashboard['low_stock']['count']);
        $this->assertSame(
            $dashboard['low_stock']['count'],
            $inventory->where('status', 'low')->count(),
            'Inventory used to call this half the reorder level, so the dashboard sent people to a shorter list.'
        );

        $this->assertSame(1, $dashboard['out_of_stock']['count']);
        $this->assertSame(
            $dashboard['out_of_stock']['count'],
            $inventory->where('status', 'out')->count()
        );
    }

    #[Test]
    public function an_empty_shelf_is_out_rather_than_low(): void
    {
        $admin = $this->admin();
        $this->product('Empty', 0, 10);

        $row = collect($this->actingAs($admin, 'staff')->getJson('/admin-api/inventory')->json('data'))->first();

        $this->assertSame('out', $row['status'], 'Nothing on the shelf is not the same as nearly nothing.');
    }

    #[Test]
    public function inventory_rows_carry_what_the_screen_needs_to_tell_them_apart(): void
    {
        $admin = $this->admin();
        $this->product('Solar Premium Series Motor Engine Oil 5W30', 40);

        $row = collect($this->actingAs($admin, 'staff')->getJson('/admin-api/inventory')->json('data'))->first();

        foreach (['unit', 'image_url', 'stock_value', 'status'] as $key) {
            $this->assertArrayHasKey($key, $row);
        }

        $this->assertEqualsWithDelta(16000, $row['stock_value'], 0.001, 'Forty bottles at four hundred each.');
    }

    #[Test]
    public function the_reports_screen_is_gone_but_its_spreadsheets_are_not(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')->get('/admin/reports')->assertNotFound();

        $this->actingAs($admin, 'staff')->get('/admin-api/reports/sales/export')->assertOk();
        $this->actingAs($admin, 'staff')->get('/admin-api/reports/inventory/export')->assertOk();
    }
}
