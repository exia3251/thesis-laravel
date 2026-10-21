<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What each staff role is shown on the dashboard.
 *
 * The dashboard was the administrator's alone, which left the two roles with
 * queues to clear without a screen telling them a queue existed. It is open
 * to all staff now, but not identical for all staff: a queue nobody in this
 * role can clear is not a notification, it is an accountant being told the
 * shelf is empty and given nothing to do about it.
 *
 * The split is by what the figure is about. Money -- collected, owed,
 * payments to verify, refunds -- goes to the people who handle money. The
 * shelf -- what is out, what is low, what is waiting to be sent -- goes to
 * the people who hold stock. The administrator holds both.
 *
 * These tests check the absences as hard as the presences, because the
 * failure that matters here is a figure reaching somebody it was not for.
 */
class DashboardByRoleTest extends TestCase
{
    use RefreshDatabase;

    private const MONEY_CARDS = ['collected', 'average_order', 'outstanding'];
    private const STOCK_CARDS = ['inventory_value'];

    private const MONEY_ACTIONS = ['awaiting_payment', 'to_verify', 'refunds'];
    private const STOCK_ACTIONS = ['out_of_stock', 'low_stock', 'to_deliver'];

    private function staff(string $role): User
    {
        return User::create([
            'email' => $role . '@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Staff Member',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    /** @return array{cards: array<int,string>, actions: array<int,string>, collections: array} */
    private function statsFor(User $user): array
    {
        $json = $this->actingAs($user, 'staff')
            ->getJson('/admin-api/dashboard/stats')
            ->assertOk()
            ->json('data');

        return [
            'cards'       => array_column($json['cards'], 'key'),
            'actions'     => array_column($json['actions'], 'key'),
            'collections' => $json['collections'],
        ];
    }

    // ------------------------------------------------------------ reaching it

    #[Test]
    public function every_staff_role_can_open_the_dashboard(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_INVENTORY_STAFF, User::ROLE_ACCOUNTING] as $role) {
            $this->actingAs($this->staff($role), 'staff')
                ->get('/admin/dashboard')
                ->assertOk();
        }
    }

    #[Test]
    public function every_staff_role_is_offered_the_link(): void
    {
        foreach ([User::ROLE_INVENTORY_STAFF, User::ROLE_ACCOUNTING] as $role) {
            $this->actingAs($this->staff($role), 'staff')
                ->get('/admin/dashboard')
                ->assertOk()
                ->assertSee('/admin/dashboard', false);
        }
    }

    #[Test]
    public function a_customer_still_cannot_reach_it(): void
    {
        $customer = User::create([
            'email' => 'shopper@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Boy Santos',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->actingAs($customer, 'staff')->getJson('/admin-api/dashboard/stats')->assertUnauthorized();
        $this->getJson('/admin-api/dashboard/stats')->assertUnauthorized();
    }

    // ---------------------------------------------------------------- the split

    #[Test]
    public function accounting_is_shown_the_money_and_not_the_shelf(): void
    {
        $stats = $this->statsFor($this->staff(User::ROLE_ACCOUNTING));

        foreach (self::MONEY_CARDS as $key) {
            $this->assertContains($key, $stats['cards'], "Accounting lost the {$key} figure.");
        }
        foreach (self::STOCK_CARDS as $key) {
            $this->assertNotContains($key, $stats['cards'], "Accounting was shown {$key}, which is not theirs.");
        }

        foreach (self::MONEY_ACTIONS as $key) {
            $this->assertContains($key, $stats['actions'], "Accounting lost the {$key} queue.");
        }
        foreach (self::STOCK_ACTIONS as $key) {
            $this->assertNotContains($key, $stats['actions'], "Accounting was notified about {$key}.");
        }
    }

    #[Test]
    public function inventory_is_shown_the_shelf_and_not_the_money(): void
    {
        $stats = $this->statsFor($this->staff(User::ROLE_INVENTORY_STAFF));

        foreach (self::STOCK_CARDS as $key) {
            $this->assertContains($key, $stats['cards'], "Inventory lost the {$key} figure.");
        }
        foreach (self::MONEY_CARDS as $key) {
            $this->assertNotContains($key, $stats['cards'], "Inventory was shown {$key}, which is not theirs.");
        }

        foreach (self::STOCK_ACTIONS as $key) {
            $this->assertContains($key, $stats['actions'], "Inventory lost the {$key} queue.");
        }
        foreach (self::MONEY_ACTIONS as $key) {
            $this->assertNotContains($key, $stats['actions'], "Inventory was notified about {$key}.");
        }
    }

    #[Test]
    public function the_administrator_is_shown_all_of_it(): void
    {
        $stats = $this->statsFor($this->staff(User::ROLE_ADMIN));

        foreach (array_merge(self::MONEY_CARDS, self::STOCK_CARDS) as $key) {
            $this->assertContains($key, $stats['cards']);
        }
        foreach (array_merge(self::MONEY_ACTIONS, self::STOCK_ACTIONS) as $key) {
            $this->assertContains($key, $stats['actions']);
        }
    }

    /**
     * How busy the shop has been is neither money nor stock, and everybody
     * working in it may know it.
     */
    #[Test]
    public function the_count_of_orders_goes_to_everybody(): void
    {
        foreach ([User::ROLE_ADMIN, User::ROLE_INVENTORY_STAFF, User::ROLE_ACCOUNTING] as $role) {
            $this->assertContains('orders', $this->statsFor($this->staff($role))['cards'], "{$role} lost the order count.");
        }
    }

    /**
     * The collections chart is money from end to end. Not sent at all rather
     * than sent and hidden in the page, or the rule is decoration.
     */
    #[Test]
    public function the_collections_chart_does_not_travel_to_inventory(): void
    {
        $this->assertSame([], $this->statsFor($this->staff(User::ROLE_INVENTORY_STAFF))['collections']);

        $this->assertNotSame([], $this->statsFor($this->staff(User::ROLE_ACCOUNTING))['collections']);
        $this->assertNotSame([], $this->statsFor($this->staff(User::ROLE_ADMIN))['collections']);
    }

    /**
     * The page must not draw an empty chart frame for somebody who is never
     * sent its data.
     */
    #[Test]
    public function the_chart_itself_is_absent_from_their_page(): void
    {
        $this->actingAs($this->staff(User::ROLE_INVENTORY_STAFF), 'staff')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('id="collectionsChart"', false);

        $this->actingAs($this->staff(User::ROLE_ACCOUNTING), 'staff')
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('id="collectionsChart"', false);
    }

    /**
     * A deactivated account keeps its role and loses everything else, so each
     * of these has to read the active flag and not the role alone.
     */
    #[Test]
    public function a_deactivated_account_is_shown_nothing(): void
    {
        $user = $this->staff(User::ROLE_ACCOUNTING);
        $user->update(['is_active' => false]);

        $this->assertFalse($user->fresh()->canViewDashboard());
        $this->assertFalse($user->fresh()->canSeeMoneyFigures());
        $this->assertFalse($user->fresh()->canSeeStockFigures());
    }
}
