<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The count beside the cart button, and where it comes from.
 *
 * The cart button said nothing when something was added to it. The item went
 * in, a small message appeared in the corner, and the one place a customer
 * looks to find out what is in their cart carried the same nothing it had a
 * second earlier -- so people added the same oil twice, or opened the cart to
 * check.
 *
 * The number comes back from the server on every call that changes the cart,
 * rather than the page adding one to what it last showed. That matters on the
 * refusals: an add that was turned down for stock must leave the badge telling
 * the truth, and a page counting its own presses would not.
 */
class CartBadgeTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'email' => 'badge@example.com',
            'password' => 'a-password',
            'full_name' => 'A Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    private function product(int $stock = 20, string $name = 'Test Oil'): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'brand' => 'Testbrand',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W-30',
            'unit' => '1 Liter',
            'price' => 500,
        ]);

        Inventory::create([
            'product_id' => $product->product_id,
            'quantity' => $stock,
            'reorder_level' => 5,
        ]);

        return $product;
    }

    // ---- What the server reports ---------------------------------------

    #[Test]
    public function adding_to_the_cart_reports_the_new_count(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 3])
            ->assertOk()
            ->assertJsonPath('cart_count', 3);
    }

    #[Test]
    public function the_count_is_bottles_rather_than_lines(): void
    {
        // Four bottles of one oil reads as four, the way the cart page totals
        // them -- not as one line.
        $first = $this->product(name: 'First Oil');
        $second = $this->product(name: 'Second Oil');

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $first->product_id, 'quantity' => 4]);

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $second->product_id, 'quantity' => 2])
            ->assertJsonPath('cart_count', 6);
    }

    #[Test]
    public function an_add_refused_for_stock_leaves_the_count_alone(): void
    {
        // The reason the number comes from the server. A page that added one
        // per press would show a bottle that was never put in the cart.
        $product = $this->product(stock: 2);

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 2])
            ->assertJsonPath('cart_count', 2);

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 5])
            ->assertStatus(400);

        $this->assertSame(2, $this->customer->fresh()->cartItemCount());
    }

    #[Test]
    public function changing_a_quantity_reports_the_new_count(): void
    {
        $product = $this->product();

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 2]);

        $line = ShoppingCart::where('user_id', $this->customer->user_id)->firstOrFail();

        $this->actingAs($this->customer, 'web')
            ->putJson("/shop-api/cart/{$line->cart_id}", ['quantity' => 7])
            ->assertOk()
            ->assertJsonPath('cart_count', 7);
    }

    #[Test]
    public function removing_a_line_reports_the_new_count(): void
    {
        $first = $this->product(name: 'First Oil');
        $second = $this->product(name: 'Second Oil');

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $first->product_id, 'quantity' => 3]);
        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $second->product_id, 'quantity' => 1]);

        $line = ShoppingCart::where('product_id', $first->product_id)->firstOrFail();

        $this->actingAs($this->customer, 'web')
            ->deleteJson("/shop-api/cart/{$line->cart_id}")
            ->assertOk()
            ->assertJsonPath('cart_count', 1);
    }

    #[Test]
    public function reading_the_cart_reports_the_count_too(): void
    {
        // The cart page corrects the header on arrival, which is how the badge
        // recovers if anything ever gets out of step.
        $product = $this->product();

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 5]);

        $this->actingAs($this->customer, 'web')
            ->getJson('/shop-api/cart')
            ->assertOk()
            ->assertJsonPath('cart_count', 5);
    }

    #[Test]
    public function one_customers_cart_never_counts_towards_anothers(): void
    {
        $other = User::create([
            'email' => 'someone-else@example.com',
            'password' => 'a-password',
            'full_name' => 'Someone Else',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $product = $this->product();

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 6]);

        $this->assertSame(0, $other->cartItemCount());
    }

    // ---- What the header draws -----------------------------------------

    #[Test]
    public function the_header_carries_the_count_on_the_first_paint(): void
    {
        // Rendered with the page rather than fetched after it. A badge that
        // arrives a moment late is the same fault with an extra request.
        $product = $this->product();

        $this->actingAs($this->customer, 'web')
            ->postJson('/shop-api/cart/add', ['product_id' => $product->product_id, 'quantity' => 4]);

        $this->actingAs($this->customer, 'web')
            ->get('/shop')
            ->assertOk()
            ->assertSee('data-badge="cart"', false)
            ->assertSee('>4</span>', false);
    }

    #[Test]
    public function an_empty_cart_draws_the_badge_hidden_rather_than_absent(): void
    {
        // So the first item added has an element to land in, and the button
        // does not change width as it appears.
        $page = $this->actingAs($this->customer, 'web')->get('/shop')->assertOk()->getContent();

        $this->assertStringContainsString('data-badge="cart"', $page);
        $this->assertMatchesRegularExpression('/data-badge="cart"[^>]*hidden/s', $page);
    }

    #[Test]
    public function a_visitor_who_is_not_signed_in_gets_no_badge(): void
    {
        $this->get('/shop')->assertOk()->assertDontSee('data-badge="cart"', false);
    }

    // ---- The orders badge ----------------------------------------------

    #[Test]
    public function the_orders_badge_counts_what_is_waiting_on_the_customer(): void
    {
        // Something owed, or goods with the courier that need confirming.
        $this->order('to_deliver', balance: 500);
        $this->order('to_receive');

        $this->assertSame(2, $this->customer->fresh()->ordersNeedingAttention());
    }

    #[Test]
    public function a_finished_order_is_not_counted_as_waiting(): void
    {
        $this->order('delivered');
        $this->order('to_receive', extra: ['received_at' => now()]);
        $this->order('to_receive', extra: ['order_status' => Sale::STATUS_CANCELLED]);

        $this->assertSame(0, $this->customer->fresh()->ordersNeedingAttention());
    }

    private function order(string $delivery, float $balance = 0, array $extra = []): Sale
    {
        return Sale::create(array_merge([
            'user_id' => $this->customer->user_id,
            'customer_name' => 'A Buyer',
            'sale_date' => now(),
            'total_amount' => 1000 + $balance,
            'paid_amount' => 1000,
            'balance_due' => $balance,
            'payment_method' => 'gcash',
            'payment_status' => $balance > 0 ? 'partial' : 'paid',
            'delivery_status' => $delivery,
            'order_status' => Sale::STATUS_ACTIVE,
            'refund_status' => Sale::REFUND_NONE,
        ], $extra));
    }
}
