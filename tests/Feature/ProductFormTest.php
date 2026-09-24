<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Adding a product, now that the shop sells one oil in several bottles.
 *
 * The shop shows one card per product_line and asks for the pack size on the
 * product page. The admin form was written before that and knew nothing about
 * it, so a new 4 litre bottle could only ever become a separate product --
 * and could not even be saved, because the three sizes of one oil share a
 * name and the name was unique outright.
 */
class ProductFormTest extends TestCase
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

    private function existing(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
            'brand' => 'CANROYAL',
            'product_line' => 'canroyal-5w30',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W-30',
            'unit' => '1 Liter',
            'price' => 420,
            'reorder_level' => 10,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_name' => 'Solar Premium Series Motor Engine Oil 5W30 API SN/CF',
            'brand' => 'SOLAR',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W-30',
            'unit' => '1 Liter',
            'price' => 400,
            'reorder_level' => 10,
        ], $overrides);
    }

    #[Test]
    public function a_new_size_joins_the_product_it_belongs_to(): void
    {
        $admin = $this->admin();
        $this->existing();

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload([
                'product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
                'brand' => 'CANROYAL',
                'unit' => '4 Liters',
                'price' => 1554,
                'product_line' => 'canroyal-5w30',
            ]))
            ->assertOk();

        $this->assertSame(
            2,
            Product::where('product_line', 'canroyal-5w30')->count(),
            'The 4 litre bottle belongs on the same page as the 1 litre one.'
        );
    }

    #[Test]
    public function one_name_may_be_used_once_per_pack_size(): void
    {
        $admin = $this->admin();
        $this->existing();

        // The same oil in a different bottle: allowed, and the ordinary case.
        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload([
                'product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
                'unit' => '5 Liters',
                'product_line' => 'canroyal-5w30',
            ]))
            ->assertOk();

        // The same oil in the same bottle: refused.
        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload([
                'product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
                'unit' => '5 Liters',
                'product_line' => 'canroyal-5w30',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_name']);
    }

    #[Test]
    public function a_product_sold_on_its_own_still_gets_a_line_of_its_own(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload())
            ->assertOk();

        $product = Product::where('brand', 'SOLAR')->first();

        $this->assertNotNull(
            $product->product_line,
            'Without a line nothing could ever be added to it, so a second size would have to be a second product.'
        );
    }

    #[Test]
    public function two_products_sold_on_their_own_do_not_share_a_line(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')->postJson('/admin-api/products', $this->payload())->assertOk();
        $this->actingAs($admin, 'staff')->postJson('/admin-api/products', $this->payload([
            'product_name' => 'Solar Premium Series Motor Engine Oil 10W30 API SN',
        ]))->assertOk();

        $this->assertSame(2, Product::distinct()->count('product_line'));
    }

    #[Test]
    public function a_line_that_does_not_exist_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload(['product_line' => 'nothing-like-this']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_line']);
    }

    #[Test]
    public function the_manufacturer_link_is_kept_with_the_rest_of_the_copy(): void
    {
        $admin = $this->admin();

        $product = $this->existing([
            'specifications' => ['summary' => 'Scraped from their site.', 'benefits' => ['Long drain']],
        ]);

        $this->actingAs($admin, 'staff')
            ->putJson("/admin-api/products/{$product->product_id}", $this->payload([
                'product_name' => $product->product_name,
                'brand' => $product->brand,
                'source_url' => 'https://canroyallubricant.com/product/example/',
            ]))
            ->assertOk();

        $specifications = $product->fresh()->specifications;

        $this->assertSame('https://canroyallubricant.com/product/example/', $specifications['source']);
        $this->assertSame('Scraped from their site.', $specifications['summary'], 'Writing the link must not wipe the rest.');
        $this->assertSame(['Long drain'], $specifications['benefits']);
    }

    #[Test]
    public function a_link_that_is_not_an_address_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/products', $this->payload(['source_url' => 'canroyal dot com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source_url']);
    }

    #[Test]
    public function editing_a_pack_does_not_split_it_off_its_own_page(): void
    {
        $admin = $this->admin();
        $product = $this->existing();

        // The form sends no line when the product is not being re-homed.
        $this->actingAs($admin, 'staff')
            ->putJson("/admin-api/products/{$product->product_id}", $this->payload([
                'product_name' => $product->product_name,
                'brand' => $product->brand,
                'price' => 450,
            ]))
            ->assertOk();

        $this->assertSame('canroyal-5w30', $product->fresh()->product_line);
    }

    #[Test]
    public function the_lines_endpoint_describes_each_product_and_its_sizes(): void
    {
        $admin = $this->admin();
        $this->existing();
        $this->existing(['unit' => '4 Liters', 'price' => 1554]);

        $lines = $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/products/lines')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $lines, 'Two bottles, one product.');
        $this->assertCount(2, $lines[0]['packs']);
        $this->assertSame('1 Liter', $lines[0]['packs'][0]['unit'], 'Cheapest first, as the shop lists them.');
    }
}
