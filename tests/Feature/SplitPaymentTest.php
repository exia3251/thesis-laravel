<?php

namespace Tests\Feature;

use App\Models\PaymentRequest;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Paying the online half of an order in more than one transfer.
 *
 * It had to arrive as a single transfer of exactly the committed amount,
 * which was a dead end rather than a rule: a fully verified GCash wallet
 * holds PHP 100,000, so the online half of a large order could not be sent
 * at all. These tests cover the arithmetic of paying it in parts, which is
 * the part that has to be right -- the money has to add up and the order
 * must not be treated as paid until it does.
 */
class SplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Receipts go to a disk of their own here, so running the suite
        // does not leave a pile of them in storage.
        Storage::fake('public');
    }

    private function customer(): User
    {
        $user = User::create([
            'email' => 'shopper@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Liza Bautista',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $user->markEmailAsVerified();

        return $user;
    }

    private function staff(): User
    {
        return User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    /** A 10,000 order, half of it committed to GCash at checkout. */
    private function splitOrder(User $user, float $total = 10000, float $gcash = 5000): Sale
    {
        return Sale::create([
            'user_id' => $user->user_id,
            'order_no' => 'ORD-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => $user->full_name,
            'contact_phone' => '09171234567',
            'delivery_address' => '1 Mabini Street, Batangas City',
            'total_amount' => $total,
            'gcash_amount' => $gcash,
            'paid_amount' => 0,
            'balance_due' => $total,
            'payment_method' => 'gcash',
            'payment_plan' => Sale::PLAN_SPLIT,
            'payment_status' => 'unpaid',
            'delivery_status' => 'to_deliver',
            'order_status' => Sale::STATUS_ACTIVE,
            'sale_date' => now(),
        ]);
    }

    /**
     * A real PNG, copied first.
     *
     * UploadedFile::fake()->image() draws one with GD, which this machine
     * does not have -- the same reason avatars are resized in the browser.
     * Copied rather than handed over directly, because storing an upload
     * moves the file it was given.
     */
    private function proof(): UploadedFile
    {
        $source = storage_path('app/public/products/canroyal-15w40.png');
        $copy = tempnam(sys_get_temp_dir(), 'proof') . '.png';

        copy($source, $copy);

        return new UploadedFile($copy, 'receipt.png', 'image/png', null, true);
    }

    private function claim(User $user, Sale $sale, float $amount, string $reference)
    {
        return $this->actingAs($user)->post("/shop-api/orders/{$sale->sale_id}/payment-requests", [
            'payment_method' => 'gcash',
            'amount' => $amount,
            'reference_no' => $reference,
            'proof_image' => $this->proof(),
        ]);
    }

    private function approve(User $staff, Sale $sale): void
    {
        $pending = PaymentRequest::where('sale_id', $sale->sale_id)->where('status', 'processing')->firstOrFail();

        $this->actingAs($staff, 'staff')
            ->putJson("/admin-api/payment-requests/{$pending->id}/approve", [])
            ->assertOk();
    }

    #[Test]
    public function a_down_payment_may_arrive_in_two_halves(): void
    {
        $customer = $this->customer();
        $staff = $this->staff();
        $order = $this->splitOrder($customer);

        // 2,500 now and 2,500 later, against a 5,000 commitment.
        $this->claim($customer, $order, 2500, 'GC-FIRST-0001')->assertOk();
        $this->approve($staff, $order);

        $this->assertEqualsWithDelta(2500, $order->fresh()->paid_amount, 0.01);
        $this->assertSame('partial', $order->fresh()->payment_status, 'Half the down payment is not a paid order.');

        $this->claim($customer, $order, 2500, 'GC-SECOND-0002')->assertOk();
        $this->approve($staff, $order);

        $order = $order->fresh();

        $this->assertEqualsWithDelta(5000, $order->paid_amount, 0.01);
        $this->assertEqualsWithDelta(5000, $order->balance_due, 0.01, 'The delivery half is still to be collected.');
        $this->assertEqualsWithDelta(0, $order->gcashOutstanding(), 0.01, 'The online commitment has been met.');
    }

    #[Test]
    public function the_online_half_of_a_large_order_can_be_sent_at_all(): void
    {
        $customer = $this->customer();
        $staff = $this->staff();

        // A wallet holds 100,000. Half of a 260,000 order does not fit in one.
        $order = $this->splitOrder($customer, 260000, 130000);

        foreach ([['GC-A-0001', 50000], ['GC-B-0002', 50000], ['GC-C-0003', 30000]] as [$reference, $amount]) {
            $this->claim($customer, $order, $amount, $reference)->assertOk();
            $this->approve($staff, $order);
        }

        $this->assertEqualsWithDelta(130000, $order->fresh()->paid_amount, 0.01);
        $this->assertEqualsWithDelta(0, $order->fresh()->gcashOutstanding(), 0.01);
    }

    #[Test]
    public function a_part_payment_cannot_be_trivially_small(): void
    {
        $customer = $this->customer();
        $order = $this->splitOrder($customer);

        $this->claim($customer, $order, 100, 'GC-TINY-0001')
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame(0, PaymentRequest::count(), 'Each claim costs a member of staff a review.');
    }

    #[Test]
    public function nobody_can_claim_more_than_the_order_owes(): void
    {
        $customer = $this->customer();
        $order = $this->splitOrder($customer);

        $this->claim($customer, $order, 99999, 'GC-TOOMUCH-0001')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function only_one_claim_can_be_waiting_at_a_time(): void
    {
        $customer = $this->customer();
        $order = $this->splitOrder($customer);

        $this->claim($customer, $order, 2500, 'GC-FIRST-0001')->assertOk();

        // The second would be reviewed against a balance the first has not
        // yet moved, so it waits its turn.
        $this->claim($customer, $order, 2500, 'GC-SECOND-0002')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function the_same_reference_cannot_be_claimed_twice(): void
    {
        $customer = $this->customer();
        $staff = $this->staff();
        $order = $this->splitOrder($customer);

        $this->claim($customer, $order, 2500, 'GC-SAME-0001')->assertOk();
        $this->approve($staff, $order);

        $this->claim($customer, $order, 2500, 'GC-SAME-0001')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function every_part_is_kept_against_the_order_for_the_receipt(): void
    {
        $customer = $this->customer();
        $staff = $this->staff();
        $order = $this->splitOrder($customer);

        $this->claim($customer, $order, 2500, 'GC-FIRST-0001')->assertOk();
        $this->approve($staff, $order);
        $this->claim($customer, $order, 2500, 'GC-SECOND-0002')->assertOk();
        $this->approve($staff, $order);

        $claims = PaymentRequest::where('sale_id', $order->sale_id)->orderBy('id')->get();

        $this->assertCount(2, $claims, 'The receipt shows each transfer with its own reference and proof.');
        $this->assertEqualsWithDelta(5000, $claims->sum('amount'), 0.01);
        $this->assertSame(['GC-FIRST-0001', 'GC-SECOND-0002'], $claims->pluck('reference_no')->all());
        $this->assertTrue($claims->every(fn ($claim) => filled($claim->proof_image_path)));
    }
}
