<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may review an oil, and what a review is worth.
 *
 * The whole value of this feature rests on one rule: a review comes from
 * somebody who bought the thing and received it. A shop whose reviews anyone
 * can write has a star rating that means nothing, and a customer reading it
 * is worse off than with no rating at all, because they think they know
 * something. These tests hold the rule from both directions -- the people it
 * lets in, and the people it keeps out.
 */
class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $email = 'buyer@example.com', string $name = 'Boy Santos'): User
    {
        return User::create([
            'email' => $email,
            'password' => 'secret12345',
            'full_name' => $name,
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

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

    private function product(string $name = 'Test Engine Oil 5W30'): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'brand' => 'SOLAR',
            'price' => 450.00,
            'viscosity_grade' => '5W30',
            'reorder_level' => 5,
        ]);

        Inventory::create(['product_id' => $product->product_id, 'quantity' => 50]);

        return $product;
    }

    /** An order for this customer containing this product, in whatever state. */
    private function order(User $user, Product $product, array $overrides = []): Sale
    {
        $sale = Sale::create(array_merge([
            'user_id' => $user->user_id,
            'order_no' => 'ORD-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => $user->full_name,
            'total_amount' => 450.00,
            'payment_method' => 'cash_on_delivery',
            'payment_plan' => Sale::PLAN_COD,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
        ], $overrides));

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 450.00,
            'subtotal' => 450.00,
        ]);

        return $sale;
    }

    // ------------------------------------------------------------ who may write

    #[Test]
    public function a_customer_who_received_it_can_review_it(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $sale = $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", [
                'rating' => 5,
                'body' => 'Ran quietly from the first drive.',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $review = ProductReview::first();

        $this->assertSame(5, $review->rating);
        // The order that entitled it is recorded, so the claim is checkable.
        $this->assertSame($sale->sale_id, $review->sale_id);
    }

    #[Test]
    public function somebody_who_never_bought_it_cannot_review_it(): void
    {
        $product = $this->product();
        $stranger = $this->customer('stranger@example.com', 'Nosy Parker');

        $this->actingAs($stranger, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 1])
            ->assertStatus(403);

        $this->assertSame(0, ProductReview::count());
    }

    /**
     * The question a review answers is what the oil was like, which nobody
     * can say while it is still with the courier.
     */
    #[Test]
    public function an_order_that_has_not_arrived_does_not_entitle_a_review(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product, ['delivery_status' => 'to_receive', 'order_status' => Sale::STATUS_ACTIVE]);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5])
            ->assertStatus(403);

        $this->assertSame(0, ProductReview::count());
    }

    #[Test]
    public function a_cancelled_order_does_not_entitle_a_review(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product, ['order_status' => Sale::STATUS_CANCELLED]);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5])
            ->assertStatus(403);
    }

    #[Test]
    public function buying_it_twice_is_not_two_opinions(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5])
            ->assertOk();

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 1])
            ->assertStatus(409);

        $this->assertSame(1, ProductReview::count());
    }

    #[Test]
    public function a_signed_out_visitor_cannot_write_one(): void
    {
        $product = $this->product();

        $this->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5])
            ->assertStatus(401);
    }

    #[Test]
    public function a_rating_has_to_be_one_to_five(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        foreach ([0, 6, -1, 99] as $bad) {
            $this->actingAs($user, 'web')
                ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => $bad])
                ->assertStatus(422);
        }

        $this->assertSame(0, ProductReview::count());
    }

    #[Test]
    public function a_rating_on_its_own_is_enough(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 4])
            ->assertOk();

        $this->assertNull(ProductReview::first()->body, 'An empty comment was stored as an empty string.');
    }

    // ------------------------------------------------------------ who may read

    #[Test]
    public function anybody_can_read_the_reviews_without_signing_in(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5, 'body' => 'Good oil.']);

        $response = $this->getJson("/shop-api/products/{$product->product_id}/reviews")->assertOk();

        $this->assertSame(1, $response->json('summary.count'));
        $this->assertSame('Good oil.', $response->json('data.0.body'));
        // A signed-out visitor is offered no form.
        $this->assertFalse($response->json('summary.can_review'));
    }

    #[Test]
    public function the_average_is_the_average(): void
    {
        $product = $this->product();

        foreach ([5, 4, 3] as $i => $rating) {
            $user = $this->customer("buyer{$i}@example.com", "Buyer Number{$i}");
            $this->order($user, $product);

            $this->actingAs($user, 'web')
                ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => $rating])
                ->assertOk();
        }

        $this->assertSame(4.0, $product->fresh()->averageRating());
        $this->assertSame(3, $product->fresh()->reviewCount());
    }

    /**
     * No reviews is not a bad score, and a product showing 0.0 would read as
     * one.
     */
    #[Test]
    public function an_unreviewed_product_has_no_rating_rather_than_zero(): void
    {
        $this->assertNull($this->product()->averageRating());
    }

    #[Test]
    public function a_surname_is_cut_to_an_initial(): void
    {
        $user = $this->customer('private@example.com', 'Maria Dela Cruz');
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 5]);

        $author = $this->getJson("/shop-api/products/{$product->product_id}/reviews")->json('data.0.author');

        $this->assertSame('Maria C.', $author);
        $this->assertStringNotContainsString('Dela Cruz', $author);
    }

    // ------------------------------------------------------------- moderation

    #[Test]
    public function an_administrator_can_take_a_review_off_the_shop(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 1, 'body' => 'unpleasant words']);

        $review = ProductReview::first();

        $this->actingAs($this->admin(), 'staff')
            ->deleteJson("/admin-api/reviews/{$review->review_id}", ['reason' => 'abusive language'])
            ->assertOk();

        // Gone from the shop, still in the table.
        $this->assertSame(0, ProductReview::count());
        $this->assertSame(1, ProductReview::withTrashed()->count());
        $this->assertSame(0, $this->getJson("/shop-api/products/{$product->product_id}/reviews")->json('summary.count'));
    }

    /**
     * A business that can quietly erase its bad reviews has a review system
     * worth nothing, so a removal has to say why and is kept.
     */
    #[Test]
    public function removing_one_requires_a_reason(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 2]);

        $review = ProductReview::first();

        $this->actingAs($this->admin(), 'staff')
            ->deleteJson("/admin-api/reviews/{$review->review_id}", [])
            ->assertStatus(422);

        $this->assertSame(1, ProductReview::count(), 'It came down without a reason being given.');
    }

    #[Test]
    public function a_removal_that_was_wrong_can_be_undone(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $this->order($user, $product);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/products/{$product->product_id}/reviews", ['rating' => 3]);

        $review = ProductReview::first();
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->deleteJson("/admin-api/reviews/{$review->review_id}", ['reason' => 'mistaken removal']);

        $this->actingAs($admin, 'staff')
            ->postJson("/admin-api/reviews/{$review->review_id}/restore")
            ->assertOk();

        $this->assertSame(1, ProductReview::count());
    }

    #[Test]
    public function the_reviews_screen_is_the_administrators_alone(): void
    {
        foreach ([User::ROLE_INVENTORY_STAFF, User::ROLE_ACCOUNTING] as $role) {
            $staff = User::create([
                'email' => $role . '@raney.test',
                'password' => 'secret12345',
                'full_name' => 'Staff Member',
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($staff, 'staff')->getJson('/admin-api/reviews')->assertForbidden();
            $this->actingAs($staff, 'staff')->get('/admin/reviews')->assertRedirect($staff->homePath());
        }

        $this->actingAs($this->admin(), 'staff')->get('/admin/reviews')->assertOk();
    }

    #[Test]
    public function a_customer_cannot_reach_the_moderation_screen(): void
    {
        $this->actingAs($this->customer(), 'staff')
            ->getJson('/admin-api/reviews')
            ->assertUnauthorized();
    }
}
