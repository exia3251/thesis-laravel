<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The spans the dashboard can be read over.
 *
 * The arithmetic is the part worth pinning down: where each window starts,
 * what it is compared against, and how finely it is cut up. A window that
 * silently starts a day late is not visible on the screen, but it moves
 * every figure on it.
 */
class DashboardRangeTest extends TestCase
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

    private function sale(User $user, string $date, float $paid = 1000): Sale
    {
        return Sale::create([
            'user_id' => $user->user_id,
            'order_no' => 'ORD-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => 'Ana Reyes',
            'contact_phone' => '09171234567',
            'delivery_address' => '1 Mabini Street, Batangas City',
            'total_amount' => $paid,
            'paid_amount' => $paid,
            'balance_due' => 0,
            'payment_method' => 'cash',
            'payment_plan' => 'cod',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'order_status' => Sale::STATUS_ACTIVE,
            'sale_date' => $date,
        ]);
    }

    private function stats(User $admin, array $query = [])
    {
        return $this->actingAs($admin, 'staff')->getJson('/admin-api/dashboard/stats?' . http_build_query($query));
    }

    #[Test]
    public function it_reads_the_last_thirty_days_by_default(): void
    {
        $admin = $this->admin();

        $range = $this->stats($admin)->assertOk()->json('data.range');

        $this->assertSame('30d', $range['key']);
        $this->assertSame(now()->subDays(29)->toDateString(), $range['from']);
        $this->assertSame(now()->toDateString(), $range['to']);
        $this->assertSame('day', $range['bucket'], 'A month of trade is readable day by day.');
    }

    #[Test]
    public function month_length_windows_start_at_the_first_of_the_month(): void
    {
        $admin = $this->admin();

        $range = $this->stats($admin, ['range' => '6m'])->assertOk()->json('data.range');

        $this->assertSame(now()->subMonthsNoOverflow(5)->startOfMonth()->toDateString(), $range['from']);
        $this->assertSame('month', $range['bucket']);
    }

    #[Test]
    public function all_time_starts_at_the_first_order(): void
    {
        $admin = $this->admin();
        $this->sale($admin, now()->subMonths(8)->toDateTimeString());
        $this->sale($admin, now()->toDateTimeString());

        $range = $this->stats($admin, ['range' => 'all'])->assertOk()->json('data.range');

        $this->assertSame(now()->subMonths(8)->toDateString(), $range['from']);
    }

    #[Test]
    public function all_time_offers_no_comparison_because_nothing_precedes_it(): void
    {
        $admin = $this->admin();
        $this->sale($admin, now()->subDays(3)->toDateTimeString());

        $cards = collect($this->stats($admin, ['range' => 'all'])->assertOk()->json('data.cards'))
            ->keyBy('key');

        $this->assertNull($cards['collected']['delta_percent']);
        $this->assertSame('across the whole period', $cards['collected']['note']);
    }

    #[Test]
    public function a_fixed_window_is_measured_against_the_one_before_it(): void
    {
        $admin = $this->admin();

        // Twice as much in the last seven days as in the seven before them.
        $this->sale($admin, now()->subDays(2)->toDateTimeString(), 2000);
        $this->sale($admin, now()->subDays(9)->toDateTimeString(), 1000);

        $cards = collect($this->stats($admin, ['range' => '7d'])->assertOk()->json('data.cards'))
            ->keyBy('key');

        $this->assertEqualsWithDelta(2000, $cards['collected']['value'], 0.001);
        $this->assertEqualsWithDelta(100, $cards['collected']['delta_percent'], 0.001);
        $this->assertSame('up', $cards['collected']['direction']);
    }

    #[Test]
    public function a_custom_window_honours_the_dates_given(): void
    {
        $admin = $this->admin();
        $this->sale($admin, '2026-03-10 09:00:00', 500);
        $this->sale($admin, '2026-05-01 09:00:00', 900);

        $data = $this->stats($admin, [
            'range' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ])->assertOk()->json('data');

        $this->assertSame('2026-03-01', $data['range']['from']);
        $this->assertSame('2026-03-31', $data['range']['to']);

        $collected = collect($data['cards'])->firstWhere('key', 'collected');
        $this->assertEqualsWithDelta(500, $collected['value'], 0.001, 'Only the March order falls inside it.');
    }

    #[Test]
    public function a_custom_window_needs_both_of_its_dates(): void
    {
        $admin = $this->admin();

        $this->stats($admin, ['range' => 'custom'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from', 'to']);
    }

    #[Test]
    public function a_custom_window_cannot_end_before_it_starts(): void
    {
        $admin = $this->admin();

        $this->stats($admin, ['range' => 'custom', 'from' => '2026-05-01', 'to' => '2026-04-01'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to']);
    }

    #[Test]
    public function an_unknown_range_is_refused_rather_than_guessed_at(): void
    {
        $admin = $this->admin();

        $this->stats($admin, ['range' => 'forever'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['range']);
    }

    #[Test]
    public function the_bucket_widens_as_the_window_does(): void
    {
        $admin = $this->admin();

        $cases = [
            ['2026-01-01', '2026-01-31', 'day'],
            ['2026-01-01', '2026-03-01', 'month'],
            ['2020-01-01', '2026-01-01', 'year'],
        ];

        foreach ($cases as [$from, $to, $expected]) {
            $range = $this->stats($admin, ['range' => 'custom', 'from' => $from, 'to' => $to])
                ->assertOk()
                ->json('data.range');

            $this->assertSame($expected, $range['bucket'], "{$from} to {$to} should be cut {$expected} by {$expected}.");
        }
    }

    #[Test]
    public function quiet_buckets_are_kept_so_the_gap_is_visible(): void
    {
        $admin = $this->admin();
        $this->sale($admin, '2026-01-05 09:00:00', 700);

        $series = $this->stats($admin, ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-07'])
            ->assertOk()
            ->json('data.collections');

        $this->assertCount(7, $series, 'Seven days asked for, seven buckets back.');
        $this->assertEqualsWithDelta(700, $series[4]['collected'], 0.001);
        $this->assertEqualsWithDelta(0, $series[0]['collected'], 0.001);
        $this->assertSame('1 Jan', $series[0]['label']);
    }

    #[Test]
    public function cancelled_orders_are_left_out_of_the_series(): void
    {
        $admin = $this->admin();
        $this->sale($admin, '2026-01-05 09:00:00', 700)
            ->update(['order_status' => Sale::STATUS_CANCELLED]);

        $series = $this->stats($admin, ['range' => 'custom', 'from' => '2026-01-01', 'to' => '2026-01-07'])
            ->assertOk()
            ->json('data.collections');

        $this->assertEqualsWithDelta(0, collect($series)->sum('collected'), 0.001);
    }

    #[Test]
    public function the_average_order_is_what_was_booked_over_how_many(): void
    {
        $admin = $this->admin();
        $this->sale($admin, now()->subDay()->toDateTimeString(), 1000);
        $this->sale($admin, now()->subDays(2)->toDateTimeString(), 3000);

        $average = collect($this->stats($admin)->assertOk()->json('data.cards'))
            ->firstWhere('key', 'average_order');

        $this->assertEqualsWithDelta(2000, $average['value'], 0.001);
    }

    #[Test]
    public function the_average_order_survives_a_period_with_no_orders(): void
    {
        $admin = $this->admin();

        $average = collect($this->stats($admin, ['range' => '7d'])->assertOk()->json('data.cards'))
            ->firstWhere('key', 'average_order');

        $this->assertEqualsWithDelta(0, $average['value'], 0.001, 'Nothing divided by nothing is not an error.');
    }

    #[Test]
    public function the_action_list_counts_the_work_waiting(): void
    {
        $admin = $this->admin();

        // Two owing, one paid but undelivered, one refund to pay back.
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'unpaid']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'partial']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['delivery_status' => 'to_receive']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update([
            'order_status' => Sale::STATUS_CANCELLED,
            'refund_status' => Sale::REFUND_PENDING,
        ]);

        $actions = collect($this->stats($admin)->assertOk()->json('data.actions'))->keyBy('key');

        $this->assertSame(2, $actions['awaiting_payment']['count']);
        $this->assertSame(1, $actions['to_deliver']['count']);
        $this->assertSame(1, $actions['refunds']['count']);
        $this->assertSame(0, $actions['to_verify']['count']);
    }

    #[Test]
    public function an_action_row_reads_as_a_sentence_either_way(): void
    {
        $admin = $this->admin();
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'unpaid']);

        $actions = collect($this->stats($admin)->assertOk()->json('data.actions'))->keyBy('key');

        $this->assertSame('order awaiting payment', $actions['awaiting_payment']['label']);

        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'unpaid']);

        $actions = collect($this->stats($admin)->assertOk()->json('data.actions'))->keyBy('key');

        $this->assertSame('orders awaiting payment', $actions['awaiting_payment']['label']);
    }

    /**
     * The point of the whole panel: the count and the page it opens have to
     * be the same set of orders, or the list is lying to whoever clicks it.
     */
    #[Test]
    public function every_action_link_lands_on_exactly_what_it_counted(): void
    {
        $admin = $this->admin();

        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'unpaid']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'partial']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['payment_status' => 'processing']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update(['delivery_status' => 'to_receive']);
        $this->sale($admin, now()->toDateTimeString(), 1000)->update([
            'order_status' => Sale::STATUS_CANCELLED,
            'refund_status' => Sale::REFUND_PENDING,
        ]);

        $actions = collect($this->stats($admin)->assertOk()->json('data.actions'))->keyBy('key');

        foreach (['awaiting_payment' => 'owing', 'to_verify' => 'processing', 'to_deliver' => 'undelivered', 'refunds' => 'refunds'] as $key => $focus) {
            $this->assertStringContainsString("focus={$focus}", $actions[$key]['href']);

            $landed = $this->actingAs($admin, 'staff')
                ->getJson("/admin-api/sales?focus={$focus}&per_page=100")
                ->assertOk()
                ->json('data');

            $this->assertCount(
                $actions[$key]['count'],
                $landed,
                "The dashboard says {$actions[$key]['count']} for {$key}; the page it opens disagrees."
            );
        }
    }

    #[Test]
    public function an_unknown_focus_is_refused_rather_than_ignored(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/sales?focus=everything')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['focus']);
    }
}
