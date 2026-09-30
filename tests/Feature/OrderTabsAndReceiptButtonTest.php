<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Which tab an order belongs under, and where the Received button appears.
 *
 * The rule for who may confirm receipt was written out three times -- in the
 * orders list, on the receipt page, and in the controller that acts on it --
 * and they drifted. The list offered the button on an order still being
 * packed, the controller then refused it, and a customer pressing a button the
 * system had drawn for them was told they could not do that.
 *
 * Grouping was the other half. An order was filed under To Pay whenever
 * anything was owed, so a part-paid order out with the courier sat in the one
 * tab where receiving makes no sense -- and offered a Received button there.
 * Once the goods are moving, where they are matters more than what is owed.
 */
class OrderTabsAndReceiptButtonTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'email' => 'tabs@example.com',
            'password' => 'a-password',
            'full_name' => 'A Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
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

    /** The row the orders list actually renders from. */
    private function listed(Sale $sale): array
    {
        $rows = $this->actingAs($this->customer, 'web')
            ->getJson('/shop-api/orders')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('sale_id', $sale->sale_id);

        $this->assertNotNull($row, 'The order was not in the list at all.');

        return $row;
    }

    // ---- The button ---------------------------------------------------

    #[Test]
    public function the_received_button_is_not_offered_while_the_order_is_still_being_packed(): void
    {
        $this->assertFalse($this->listed($this->order('to_deliver'))['can_confirm_receipt']);
    }

    #[Test]
    public function the_received_button_is_offered_once_it_is_with_the_courier(): void
    {
        $this->assertTrue($this->listed($this->order('to_receive'))['can_confirm_receipt']);
    }

    #[Test]
    public function the_received_button_is_not_offered_on_an_order_already_confirmed(): void
    {
        $sale = $this->order('to_receive', extra: ['received_at' => now()]);

        $this->assertFalse($this->listed($sale)['can_confirm_receipt']);
    }

    #[Test]
    public function the_received_button_is_not_offered_on_a_cancelled_order(): void
    {
        $sale = $this->order('to_receive', extra: ['order_status' => Sale::STATUS_CANCELLED]);

        $this->assertFalse($this->listed($sale)['can_confirm_receipt']);
    }

    #[Test]
    public function the_received_button_is_not_offered_on_an_order_already_delivered(): void
    {
        $this->assertFalse($this->listed($this->order('delivered'))['can_confirm_receipt']);
    }

    /**
     * The point of the whole exercise: whatever the list offers, the
     * controller accepts. These two disagreeing is the bug, not either one of
     * them being wrong on its own.
     */
    #[Test]
    public function what_the_list_offers_is_what_the_server_accepts(): void
    {
        foreach (['to_deliver', 'to_receive', 'delivered'] as $delivery) {
            $sale = $this->order($delivery);
            $offered = $this->listed($sale)['can_confirm_receipt'];

            $response = $this->actingAs($this->customer, 'web')
                ->postJson("/shop-api/orders/{$sale->sale_id}/receipt");

            $this->assertSame(
                $offered,
                $response->status() === 200,
                "The list and the server disagree about confirming a {$delivery} order."
            );
        }
    }

    // ---- The tabs ------------------------------------------------------

    #[Test]
    public function an_order_out_for_delivery_is_filed_under_to_receive_even_with_money_owed(): void
    {
        // A split payment: half sent, half due on arrival, goods dispatched.
        $sale = $this->order('to_receive', balance: 500);

        $row = $this->listed($sale);

        $this->assertSame('to_receive', $row['status_group']);
        $this->assertTrue($row['can_confirm_receipt']);
    }

    #[Test]
    public function an_unpaid_order_still_being_packed_is_filed_under_to_pay(): void
    {
        $this->assertSame('to_pay', $this->listed($this->order('to_deliver', balance: 500))['status_group']);
    }

    #[Test]
    public function no_order_under_to_pay_offers_a_received_button(): void
    {
        // The reviewer's finding, stated as the rule it broke.
        $this->order('to_deliver', balance: 500);
        $this->order('to_deliver');
        $this->order('to_receive', balance: 500);
        $this->order('delivered');

        $rows = $this->actingAs($this->customer, 'web')
            ->getJson('/shop-api/orders')
            ->assertOk()
            ->json('data');

        foreach ($rows as $row) {
            if ($row['status_group'] === 'to_pay') {
                $this->assertFalse(
                    $row['can_confirm_receipt'],
                    "Order #{$row['sale_id']} offers Received while sitting under To Pay."
                );
            }
        }
    }

    #[Test]
    public function a_delivered_order_is_filed_under_delivered(): void
    {
        $this->assertSame('delivered', $this->listed($this->order('delivered'))['status_group']);
    }

    #[Test]
    public function a_cancelled_order_is_filed_under_cancelled_whatever_else_is_true(): void
    {
        $sale = $this->order('to_receive', balance: 500, extra: ['order_status' => Sale::STATUS_CANCELLED]);

        $this->assertSame('cancelled', $this->listed($sale)['status_group']);
    }

    #[Test]
    public function every_order_lands_in_exactly_one_of_the_four_tabs(): void
    {
        // Nothing may fall between the tabs: an order in no group is invisible
        // on a page whose only view of an order is those four buttons.
        foreach (['to_deliver', 'to_receive', 'delivered'] as $delivery) {
            foreach ([0, 500] as $balance) {
                $group = $this->order($delivery, balance: $balance)->statusGroup();

                $this->assertContains($group, ['to_pay', 'to_receive', 'delivered', 'cancelled']);
            }
        }
    }
}
