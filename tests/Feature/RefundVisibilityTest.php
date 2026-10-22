<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A refund a customer asked for, and whether anybody here can see it.
 *
 * A customer may only ask for a refund on an order that has been delivered
 * and not cancelled. The back office drew its refund panel inside the
 * branch for cancelled orders. Those two conditions cannot both be true, so
 * the panel could never appear for a request a customer actually made: the
 * reason reached the database, the Refunds filter found the order, and
 * opening it offered "Confirm delivery" and nothing else -- no reason, no
 * amount, and no way to record the payment back.
 *
 * The reason is the part that matters. Somebody deciding what to send back
 * should be reading why it was asked for.
 */
class RefundVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::create([
            'email' => 'buyer@example.com',
            'password' => 'secret12345',
            'full_name' => 'Boy Santos',
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

    /** A delivered, paid order: the only state a refund may be asked on. */
    private function deliveredOrder(User $user): Sale
    {
        $product = Product::create([
            'product_name' => 'Test Engine Oil 5W30',
            'brand' => 'SOLAR',
            'oil_type' => 'Synthetic',
            'viscosity_grade' => '5W30',
            'unit' => '1 Liter',
            'price' => 450,
            'reorder_level' => 5,
        ]);
        Inventory::create(['product_id' => $product->product_id, 'quantity' => 20]);

        $sale = Sale::create([
            'user_id' => $user->user_id,
            'customer_name' => $user->full_name,
            'total_amount' => 450,
            'paid_amount' => 450,
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

        return $sale;
    }

    #[Test]
    public function the_reason_a_customer_gives_is_kept(): void
    {
        $user = $this->customer();
        $sale = $this->deliveredOrder($user);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", [
                'reason' => 'Two of the bottles arrived with the seals broken.',
            ])
            ->assertOk();

        $sale->refresh();

        $this->assertSame(Sale::REFUND_PENDING, $sale->refund_status);
        $this->assertSame('Two of the bottles arrived with the seals broken.', $sale->refund_reason);
    }

    /**
     * The order the back office reads is the one the customer wrote on, so
     * the reason has to survive the journey to the admin screen.
     */
    #[Test]
    public function the_reason_reaches_the_back_office(): void
    {
        $user = $this->customer();
        $sale = $this->deliveredOrder($user);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", [
                'reason' => 'Wrong viscosity sent, I ordered 5W30.',
            ])->assertOk();

        $data = $this->actingAs($this->admin(), 'staff')
            ->getJson("/admin-api/sales/{$sale->sale_id}")
            ->assertOk()
            ->json('data');

        $this->assertSame('Wrong viscosity sent, I ordered 5W30.', $data['refund_reason']);
        $this->assertSame(Sale::REFUND_PENDING, $data['refund_status']);
    }

    /** It has to be findable, which is what the Refunds filter is for. */
    #[Test]
    public function the_order_appears_under_refunds_to_process(): void
    {
        $user = $this->customer();
        $sale = $this->deliveredOrder($user);

        $this->actingAs($user, 'web')
            ->postJson("/shop-api/orders/{$sale->sale_id}/refund-request", ['reason' => 'Damaged on arrival.'])
            ->assertOk();

        $rows = $this->actingAs($this->admin(), 'staff')
            ->getJson('/admin-api/sales?focus=refunds')
            ->assertOk()
            ->json('data');

        $this->assertContains(
            $sale->sale_id,
            array_column($rows, 'sale_id'),
            'A refund the customer asked for is missing from the queue meant to list it.'
        );
    }

    /**
     * The panel was nested inside the cancelled branch, where a
     * customer-requested refund can never reach it. These read the view,
     * because the fault was in which branch drew it and no request can see
     * that.
     */
    #[Test]
    public function the_refund_panel_is_not_trapped_inside_the_cancelled_branch(): void
    {
        $view = file_get_contents(resource_path('views/admin/sales.blade.php'));

        $this->assertStringContainsString(
            'function renderRefundPanel(sale)',
            $view,
            'The refund panel is no longer drawn by a function of its own.'
        );

        // Drawn for any order that has a refund, not only a cancelled one.
        $this->assertStringContainsString("const refund = renderRefundPanel(sale);", $view);

        // And it is the reason that is shown, not just the amount.
        $this->assertStringContainsString('sale.refund_reason', $view);
    }

    /** The person choosing what to send back should be reading why. */
    #[Test]
    public function the_reason_is_in_front_of_whoever_records_the_payment(): void
    {
        $view = file_get_contents(resource_path('views/admin/sales.blade.php'));

        $this->assertStringContainsString('id="rfReason"', $view);
        $this->assertStringContainsString("document.getElementById('rfReason').textContent = sale.refund_reason", $view);
    }

    /** A cancellation reason was already shown, and must stay shown. */
    #[Test]
    public function a_cancellation_reason_is_still_shown(): void
    {
        $user = $this->customer();
        $sale = $this->deliveredOrder($user);

        $sale->forceFill([
            'order_status' => Sale::STATUS_CANCELLED,
            'cancellation_reason' => 'Ordered the wrong pack size.',
        ])->save();

        $data = $this->actingAs($this->admin(), 'staff')
            ->getJson("/admin-api/sales/{$sale->sale_id}")
            ->assertOk()
            ->json('data');

        $this->assertSame('Ordered the wrong pack size.', $data['cancellation_reason']);

        $view = file_get_contents(resource_path('views/admin/sales.blade.php'));
        $this->assertStringContainsString('sale.cancellation_reason', $view);
    }
}
