<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the cart accepts as a quantity.
 *
 * The rule used to be `nullable`, defaulting to one. An empty or non-numeric
 * quantity box produces NaN, JSON serialises that to null, and the cart took
 * it as a quantity of one -- so typing letters into the box added a bottle
 * and reported success. These pin the endpoint against that, independently
 * of whatever the page happens to send.
 */
class CartQuantityTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        $user = User::create([
            'email' => 'qty@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Quantity Tester',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $user->markEmailAsVerified();

        return $user;
    }

    private function product(int $stock = 40): Product
    {
        $product = Product::create([
            'product_name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
            'brand' => 'CANROYAL',
            'product_line' => 'canroyal-5w30',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W30',
            'unit' => '1 Liter',
            'price' => 650,
            'reorder_level' => 10,
        ]);

        Inventory::create([
            'product_id' => $product->product_id,
            'quantity' => $stock,
            'last_updated' => now(),
        ]);

        return $product;
    }

    public static function rejected(): array
    {
        return [
            // what a broken quantity box sends, and why it is not a quantity
            'null from a NaN quantity' => [null],
            'empty string'             => [''],
            'zero'                     => [0],
            'negative'                 => [-3],
            'letters'                  => ['abc'],
            'a fraction'               => [2.5],
            'more than we sell'        => [1000],
        ];
    }

    #[Test]
    #[DataProvider('rejected')]
    public function it_refuses_a_quantity_that_is_not_a_whole_number_of_at_least_one(mixed $quantity): void
    {
        $user = $this->customer();
        $product = $this->product();

        $this->actingAs($user)
            ->postJson('/shop-api/cart/add', [
                'product_id' => $product->product_id,
                'quantity' => $quantity,
            ])
            ->assertStatus(422);

        $this->assertSame(0, ShoppingCart::count(), 'Nothing should have reached the cart.');
    }

    #[Test]
    public function it_accepts_a_whole_number(): void
    {
        $user = $this->customer();
        $product = $this->product();

        $this->actingAs($user)
            ->postJson('/shop-api/cart/add', [
                'product_id' => $product->product_id,
                'quantity' => 3,
            ])
            ->assertOk();

        $this->assertSame(3, ShoppingCart::first()->quantity);
    }

    #[Test]
    public function it_refuses_more_than_there_is_in_stock(): void
    {
        $user = $this->customer();
        $product = $this->product(5);

        $this->actingAs($user)
            ->postJson('/shop-api/cart/add', [
                'product_id' => $product->product_id,
                'quantity' => 6,
            ])
            ->assertStatus(400);

        $this->assertSame(0, ShoppingCart::count());
    }
}
