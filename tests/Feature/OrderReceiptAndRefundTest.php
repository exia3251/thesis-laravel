<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may say an order arrived, and what is offered once it has.
 *
 * Confirming receipt was open from the moment the order existed, and it did
 * more than acknowledge: with nothing owed it also set the order delivered.
 * So a customer could complete an order whose goods were still on the shelf
 * here, and the warehouse would see a finished order for something nobody had
 * sent.
 *
 * The other half is what replaces cancelling. A delivered order cannot be
 * called back, so the same place offers the thing that is still possible --
 * asking for the money -- and it joins the refund list staff already work
 * from rather than inventing a second queue.
 */
class OrderReceiptAndRefundTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'email' => 'buyer@example.com',
            'password' => 'a-password',
            'full_name' => 'A Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    private function order(string $delivery, float $paid = 1000, float $balance = 0): Sale
    {
        return Sale::create([
            'user_id' => $this->customer->user_id,
            'customer_name' => 'A Buyer',
            'sale_date' => now(),
            'total_amount' => $paid + $balance,
            'paid_amount' => $paid,
            'balance_due' => $balance,
            'payment_method' => 'gcash',
            'payment_status' => $balance > 0 ? 'partial' : 'paid',
            'delivery_status' => $delivery,
            'order_status' => Sale::STATUS_ACTIVE,
            'refund_status' => Sale::REFUND_NONE,
        ]);
    }

    #[Test]
    public function an_order_still_being_packed_cannot_be_confirmed_as_received(): void
    {
        $sale = $this->order('to_deliver');

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/receipt")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'This order has not been sent out yet. You can confirm it once it is with the courier.']);

        $this->assertNull($sale->fresh()->received_at);
        $this->assertSame('to_deliver', $sale->fresh()->delivery_status);
    }

    #[Test]
    public function an_order_with_the_courier_can_be_confirmed(): void
    {
        $sale = $this->order('to_receive');

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/receipt")
            ->assertOk();

        $this->assertNotNull($sale->fresh()->received_at);
    }

    #[Test]
    public function a_delivered_order_can_have_a_refund_asked_for(): void
    {
        $sale = $this->order('delivered', paid: 3000);

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'Two bottles arrived leaking.'])
            ->assertOk();

        $sale->refresh();

        $this->assertSame(Sale::REFUND_PENDING, $sale->refund_status);
        $this->assertSame('3000.00', (string) $sale->refund_amount);
        $this->assertSame('Two bottles arrived leaking.', $sale->refund_reason);
    }

    #[Test]
    public function a_refund_request_has_to_say_what_is_wrong(): void
    {
        $sale = $this->order('delivered');

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", [])
            ->assertStatus(422);

        $this->assertSame(Sale::REFUND_NONE, $sale->fresh()->refund_status);
    }

    #[Test]
    public function a_refund_cannot_be_asked_for_before_it_arrives(): void
    {
        // There is still something to cancel at that point, which returns the
        // money without anybody having to decide.
        $sale = $this->order('to_receive');

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'changed my mind'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'This order has not been delivered yet. You can still cancel it instead.']);
    }

    #[Test]
    public function a_refund_cannot_be_asked_for_twice(): void
    {
        $sale = $this->order('delivered');

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'first'])
            ->assertOk();

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'again'])
            ->assertStatus(422);
    }

    #[Test]
    public function nothing_paid_means_nothing_to_refund(): void
    {
        $sale = $this->order('delivered', paid: 0, balance: 0);

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'something'])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Nothing has been paid on this order, so there is nothing to refund.']);
    }

    #[Test]
    public function a_requested_refund_reaches_the_queue_staff_already_work_from(): void
    {
        $sale = $this->order('delivered', paid: 2500);

        $this->actingAs($this->customer, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'wrong grade sent'])
            ->assertOk();

        // The same filter the dashboard's "Refunds to process" counts.
        $this->assertTrue(
            Sale::where('refund_status', Sale::REFUND_PENDING)->where('sale_id', $sale->sale_id)->exists()
        );
    }
}
