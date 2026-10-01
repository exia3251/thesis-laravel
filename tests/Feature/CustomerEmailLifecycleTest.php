<?php

namespace Tests\Feature;

use App\Mail\OrderPlaced;
use App\Models\CustomerProfile;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the shop asks of an address, and what it sends to one.
 *
 * Two doors lead to a customer account and they treat the address
 * differently, for the same reason. Registering with an email proves
 * nothing yet, so the shop sends a link and waits. Arriving through Google
 * proves it already, so there is nothing to wait for.
 *
 * What both end at is the same rule: an order is only accepted once the
 * address behind it has been established, because an order generates a
 * receipt, a payment decision and a delivery notice, and every one of those
 * goes to that address.
 */
class CustomerEmailLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'email' => 'buyer@example.com',
            'password' => 'secret12345',
            'full_name' => 'Boy Santos',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ], $overrides));

        CustomerProfile::create([
            'user_id' => $user->user_id,
            'phone' => '09123456789',
            'house_street' => '123 Main Street',
            'barangay' => 'Bagumbayan',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'postal_code' => '1100',
        ]);

        return $user;
    }

    private function stockedProduct(): Product
    {
        $product = Product::create([
            'product_name' => 'Test Engine Oil 5W30',
            'brand' => 'SOLAR',
            'price' => 450.00,
            'viscosity_grade' => '5W30',
            'reorder_level' => 5,
        ]);

        Inventory::create([
            'product_id' => $product->product_id,
            'quantity' => 100,
        ]);

        return $product;
    }

    private function withCart(User $user): void
    {
        ShoppingCart::create([
            'user_id' => $user->user_id,
            'product_id' => $this->stockedProduct()->product_id,
            'quantity' => 2,
        ]);
    }

    // ---------------------------------------------------------------- signing up

    #[Test]
    public function registering_with_an_email_leaves_it_unconfirmed_and_asks(): void
    {
        Mail::fake();

        $this->postJson('/shop/register', [
            'full_name' => 'New Customer',
            'email' => 'fresh@example.com',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
            'phone' => '09123456789',
            'house_street' => '123 Main Street',
            'barangay' => 'Bagumbayan',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'postal_code' => '1100',
        ])->assertOk()->assertJson(['success' => true]);

        $user = User::where('email', 'fresh@example.com')->first();

        $this->assertNotNull($user, 'The account was not created.');
        $this->assertNull(
            $user->email_verified_at,
            'An address nobody has proved was treated as proved.'
        );
    }

    // -------------------------------------------------------------- placing orders

    #[Test]
    public function an_unconfirmed_address_cannot_place_an_order(): void
    {
        Mail::fake();

        $user = $this->customer();
        $user->forceFill(['email_verified_at' => null])->save();
        $this->withCart($user);

        $response = $this->actingAs($user, 'web')
            ->postJson('/shop-api/orders', ['payment_plan' => 'cod'])
            ->assertStatus(422);

        $this->assertTrue($response->json('needs_verification'));

        // And nothing was sent to an address that has not been established.
        Mail::assertNotSent(OrderPlaced::class);
    }

    #[Test]
    public function a_confirmed_address_can_place_one_and_is_sent_the_receipt(): void
    {
        Mail::fake();

        $user = $this->customer();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->withCart($user);

        $this->actingAs($user, 'web')
            ->postJson('/shop-api/orders', ['payment_plan' => 'cod'])
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    }

    /**
     * The whole point of the difference between the two doors: somebody who
     * arrived through Google has already proved the address, so they are not
     * held at checkout waiting for an email that will never be needed.
     */
    #[Test]
    public function a_customer_who_arrived_through_google_is_not_held_at_checkout(): void
    {
        Mail::fake();

        $user = $this->customer([
            'email' => 'googler@example.com',
            'password' => null,
            'google_id' => 'google-sub-99',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->withCart($user);

        $this->actingAs($user, 'web')
            ->postJson('/shop-api/orders', ['payment_plan' => 'cod'])
            ->assertOk();

        // The receipt goes to the Google address like any other.
        Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo('googler@example.com'));
    }

    // ------------------------------------------------------------- signing in

    /**
     * An account made through Google has no password at all. Checking an
     * empty one against a typed one has to refuse rather than fail, and has
     * to refuse in the same words as any other wrong password, or the form
     * becomes a way of asking which accounts have no password.
     */
    #[Test]
    public function an_account_with_no_password_cannot_be_signed_into_with_one(): void
    {
        $this->customer([
            'email' => 'googler@example.com',
            'password' => null,
            'google_id' => 'google-sub-98',
        ]);

        $response = $this->postJson('/shop/login', [
            'email' => 'googler@example.com',
            'password' => 'anything-at-all',
        ])->assertStatus(401);

        $this->assertGuest('web');
        $this->assertStringContainsString('Invalid credentials', $response->json('message'));
    }

    // ------------------------------------------------------- mail cannot break work

    /**
     * Mail goes out inside the request that triggers it, so an unreachable
     * mail server must not take the order down with it. The order is the
     * real work; the message about it is not.
     */
    #[Test]
    public function an_order_survives_the_mail_server_being_unreachable(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP is down'));

        $user = $this->customer();
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->withCart($user);

        $this->actingAs($user, 'web')
            ->postJson('/shop-api/orders', ['payment_plan' => 'cod'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('sales', ['user_id' => $user->user_id]);
    }
}
