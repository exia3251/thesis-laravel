<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Buying by the box.
 *
 * A box is six of the same bottle rather than a product of its own, so what
 * has to hold is arithmetic: order one box, six leave the shelf. These pin
 * that, and pin the stock check that stops someone ordering a box that is not
 * there -- which is the same check that stops overselling generally, and had
 * nothing covering it before.
 */
class BulkBoxOrderTest extends TestCase
{
    use RefreshDatabase;

    private const BOX = 6;

    private function customer(): User
    {
        $user = User::create([
            'email' => 'box@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Box Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        // Not mass-assignable, so passing it to create() above would be
        // dropped and every order here would stop at the verification gate.
        $user->markEmailAsVerified();

        CustomerProfile::create([
            'user_id' => $user->user_id,
            'phone' => '09171234567',
            'house_street' => '12 Mabini Street',
            'barangay' => 'San Roque',
            'city' => 'Batangas City',
            'province' => 'Batangas',
            'postal_code' => '4200',
        ]);

        return $user;
    }

    private function product(int $stock): Product
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

    #[Test]
    public function ordering_one_box_takes_six_off_the_shelf(): void
    {
        $user = $this->customer();
        $product = $this->product(40);

        ShoppingCart::create([
            'user_id' => $user->user_id,
            'product_id' => $product->product_id,
            'quantity' => self::BOX,
        ]);

        $response = $this->actingAs($user)->postJson('/shop-api/orders', [
            'payment_plan' => Sale::PLAN_COD,
        ]);

        $response->assertOk();

        $this->assertSame(
            34,
            Inventory::where('product_id', $product->product_id)->value('quantity'),
            'A box of six should leave 34 of the 40 that were on the shelf.'
        );
    }

    #[Test]
    public function the_order_is_charged_per_bottle_not_per_box(): void
    {
        $user = $this->customer();
        $product = $this->product(40);

        ShoppingCart::create([
            'user_id' => $user->user_id,
            'product_id' => $product->product_id,
            'quantity' => self::BOX,
        ]);

        $this->actingAs($user)->postJson('/shop-api/orders', [
            'payment_plan' => Sale::PLAN_COD,
        ])->assertOk();

        // 6 x 650. A box that priced itself would be the easy place for a
        // rounding or multiplier bug to hide.
        $this->assertEquals(3900.00, (float) Sale::latest('sale_id')->value('total_amount'));
    }

    #[Test]
    public function a_box_that_is_not_on_the_shelf_is_refused(): void
    {
        $user = $this->customer();
        $product = $this->product(5);

        ShoppingCart::create([
            'user_id' => $user->user_id,
            'product_id' => $product->product_id,
            'quantity' => self::BOX,
        ]);

        $this->actingAs($user)->postJson('/shop-api/orders', [
            'payment_plan' => Sale::PLAN_COD,
        ])->assertStatus(400);

        $this->assertSame(
            5,
            Inventory::where('product_id', $product->product_id)->value('quantity'),
            'A refused order must not have moved stock.'
        );

        $this->assertSame(0, Sale::count());
    }

    #[Test]
    public function the_cart_refuses_more_boxes_than_there_is_stock_for(): void
    {
        $user = $this->customer();
        $product = $this->product(5);

        $this->actingAs($user)->postJson('/shop-api/cart/add', [
            'product_id' => $product->product_id,
            'quantity' => self::BOX,
        ])->assertStatus(400);

        $this->assertSame(0, ShoppingCart::count());
    }
}
