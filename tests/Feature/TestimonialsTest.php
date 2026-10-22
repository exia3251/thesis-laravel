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
 * The strip of customer opinion on the shop's front page.
 *
 * It is drawn from the reviews themselves, so the shop cannot claim
 * anything nobody wrote. Every review that exists came from a delivered
 * order, which is why the page may say "verified purchase" without
 * qualifying it.
 *
 * What it must not become is a wall of five stars. A shop showing only its
 * flattering reviews has a rating that means nothing, and the average
 * printed above them would not match the reviews beneath it.
 */
class TestimonialsTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name = 'Test Engine Oil 5W30'): Product
    {
        $product = Product::create([
            'product_name' => $name,
            'brand' => 'SOLAR',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W30',
            'unit' => '1 Liter',
            'price' => 450,
            'reorder_level' => 5,
        ]);
        Inventory::create(['product_id' => $product->product_id, 'quantity' => 20]);

        return $product;
    }

    /** A review written the way the application writes one. */
    private function review(Product $product, int $rating, ?string $body, string $name = 'Boy Santos'): ProductReview
    {
        static $n = 0;
        $n++;

        $user = User::create([
            'email' => "buyer{$n}@example.com",
            'password' => 'secret12345',
            'full_name' => $name,
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $sale = Sale::create([
            'user_id' => $user->user_id,
            'customer_name' => $user->full_name,
            'total_amount' => 450,
            'payment_method' => 'cash_on_delivery',
            'payment_plan' => Sale::PLAN_COD,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
        ]);

        SaleItem::create([
            'sale_id' => $sale->sale_id,
            'product_id' => $product->product_id,
            'quantity' => 1,
            'unit_price' => 450,
            'subtotal' => 450,
        ]);

        return ProductReview::create([
            'product_id' => $product->product_id,
            'user_id'    => $user->user_id,
            'sale_id'    => $sale->sale_id,
            'rating'     => $rating,
            'body'       => $body,
        ]);
    }

    #[Test]
    public function the_strip_is_open_to_a_visitor_who_is_not_signed_in(): void
    {
        $product = $this->product();
        $this->review($product, 5, 'Ran quietly from the first drive.');

        $body = $this->getJson('/shop-api/testimonials')->assertOk()->json();

        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);
    }

    /**
     * The whole point. A shop that hides its poor reviews has a rating
     * nobody should believe.
     */
    #[Test]
    public function an_unflattering_review_is_shown_like_any_other(): void
    {
        $product = $this->product();

        $this->review($product, 5, 'Excellent oil, will buy again.');
        $this->review($product, 1, 'Arrived with a broken seal and nobody answered my email.');

        $ratings = array_column($this->getJson('/shop-api/testimonials')->json('data'), 'rating');

        $this->assertContains(1, $ratings, 'The one-star review was filtered out of the shop front.');
        $this->assertContains(5, $ratings);
    }

    #[Test]
    public function the_average_matches_the_reviews_behind_it(): void
    {
        $product = $this->product();

        foreach ([5, 4, 3] as $rating) {
            $this->review($product, $rating, "A review scoring {$rating}.");
        }

        $summary = $this->getJson('/shop-api/testimonials')->json('summary');

        $this->assertEquals(4.0, $summary['average']);
        $this->assertSame(3, $summary['count']);
    }

    /**
     * A rating with no words has nothing to quote, but it still counts
     * towards the figure above the strip.
     */
    #[Test]
    public function a_rating_without_words_counts_but_is_not_quoted(): void
    {
        $product = $this->product();

        $this->review($product, 5, 'Good stuff.');
        $this->review($product, 1, null);

        $body = $this->getJson('/shop-api/testimonials')->json();

        $this->assertCount(1, $body['data'], 'A review with no words was quoted as though it had some.');
        $this->assertSame(2, $body['summary']['count'], 'The silent review was left out of the count as well.');
        $this->assertEquals(3.0, $body['summary']['average']);
    }

    #[Test]
    public function a_shop_with_no_reviews_says_nothing_rather_than_showing_an_empty_heading(): void
    {
        $body = $this->getJson('/shop-api/testimonials')->assertOk()->json();

        $this->assertSame([], $body['data']);
        $this->assertNull($body['summary']['average']);

        // The section starts hidden and is only revealed once there is
        // something in it.
        $view = file_get_contents(resource_path('views/customer/shop.blade.php'));
        $this->assertStringContainsString('id="testimonials" class="mb-8 hidden"', $view);
        $this->assertStringContainsString("if (!data.length) return;", $view);
    }

    #[Test]
    public function a_review_the_business_removed_is_off_the_shop_front(): void
    {
        $product = $this->product();

        $kept = $this->review($product, 5, 'A review worth keeping.');
        $gone = $this->review($product, 1, 'Something the business took down.');

        $gone->delete();

        $bodies = array_column($this->getJson('/shop-api/testimonials')->json('data'), 'body');

        $this->assertContains('A review worth keeping.', $bodies);
        $this->assertNotContains('Something the business took down.', $bodies);
        $this->assertSame(1, $this->getJson('/shop-api/testimonials')->json('summary.count'));
        $this->assertNotNull($kept->fresh());
    }

    /**
     * A surname is more than a stranger needs in order to read an opinion
     * about engine oil.
     */
    #[Test]
    public function only_a_first_name_and_an_initial_are_shown(): void
    {
        $product = $this->product();
        $this->review($product, 5, 'Very good.', 'Maria Dela Cruz');

        $author = $this->getJson('/shop-api/testimonials')->json('data.0.author');

        $this->assertSame('Maria C.', $author);
        $this->assertStringNotContainsString('Dela Cruz', $author);
    }

    /**
     * The demo accounts are tagged "[demo]" so the business can tell them
     * apart. That marker is for the back office, and was being printed on
     * the shop front as the customer's first name.
     */
    #[Test]
    public function a_bracketed_tag_on_an_account_never_reaches_the_shop_front(): void
    {
        $product = $this->product();
        $this->review($product, 5, 'Reliable supplier.', '[demo] Ramon Villanueva');

        $author = $this->getJson('/shop-api/testimonials')->json('data.0.author');

        $this->assertSame('Ramon V.', $author);
        $this->assertStringNotContainsString('demo', $author);
        $this->assertStringNotContainsString('[', $author);
    }

    #[Test]
    public function the_newest_are_shown_first_and_the_strip_is_bounded(): void
    {
        $product = $this->product();

        for ($i = 1; $i <= 9; $i++) {
            $review = $this->review($product, 5, "Review number {$i}.");
            $review->forceFill(['created_at' => now()->subDays(10 - $i)])->save();
        }

        $data = $this->getJson('/shop-api/testimonials')->json('data');

        $this->assertLessThanOrEqual(6, count($data), 'The whole table is being sent to the front page.');
        $this->assertSame('Review number 9.', $data[0]['body'], 'The newest is not first.');
    }
}
