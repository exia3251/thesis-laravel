<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Who may read the Analytics screen.
 *
 * It used to be whoever could read the Dashboard, which meant the
 * administrator alone. That left Accounting able to download the sales and
 * inventory figures as spreadsheets -- those exports have always been
 * theirs -- while being refused the page that shows the same figures. The
 * screen is gated on view_reports now, for the same reason the exports are.
 *
 * The Dashboard is deliberately not included. It is the shop's own running
 * state, and it is a separate decision from reading the figures.
 */
class AnalyticsAccessTest extends TestCase
{
    use RefreshDatabase;

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

    #[Test]
    public function accounting_can_open_the_analytics_screen(): void
    {
        $this->actingAs($this->staff(User::ROLE_ACCOUNTING), 'staff')
            ->get('/admin/analytics')
            ->assertOk();
    }

    #[Test]
    public function accounting_can_read_the_figures_behind_it(): void
    {
        $this->actingAs($this->staff(User::ROLE_ACCOUNTING), 'staff')
            ->getJson('/admin-api/analytics')
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    /**
     * The page offers an export. Offering a button that answers 403 is worse
     * than not offering it.
     */
    #[Test]
    public function the_export_the_screen_offers_works_for_them_too(): void
    {
        $this->actingAs($this->staff(User::ROLE_ACCOUNTING), 'staff')
            ->get('/admin-api/reports/sales/export')
            ->assertOk();
    }

    #[Test]
    public function the_administrator_still_has_it(): void
    {
        $admin = $this->staff(User::ROLE_ADMIN);

        $this->actingAs($admin, 'staff')->get('/admin/analytics')->assertOk();
        $this->actingAs($admin, 'staff')->getJson('/admin-api/analytics')->assertOk();
    }

    /**
     * A refused page sends the browser to wherever that role does belong,
     * rather than showing it a raw error; a refused request for data is a
     * plain 403. Both are checked, because the page and the figures behind
     * it are two separate doors into the same room.
     */
    #[Test]
    public function inventory_staff_still_do_not(): void
    {
        $inventory = $this->staff(User::ROLE_INVENTORY_STAFF);

        $this->actingAs($inventory, 'staff')
            ->get('/admin/analytics')
            ->assertRedirect($inventory->homePath());

        $this->actingAs($inventory, 'staff')
            ->getJson('/admin-api/analytics')
            ->assertForbidden();
    }

    /**
     * Reading the figures is not the same decision as reading the shop's
     * running state, and this change was only the first of the two.
     */
    #[Test]
    public function the_dashboard_is_still_the_administrators_alone(): void
    {
        foreach ([User::ROLE_ACCOUNTING, User::ROLE_INVENTORY_STAFF] as $role) {
            $user = $this->staff($role);

            $this->actingAs($user, 'staff')
                ->get('/admin/dashboard')
                ->assertRedirect($user->homePath());

            $this->actingAs($user, 'staff')
                ->getJson('/admin-api/dashboard/stats')
                ->assertForbidden();
        }
    }

    #[Test]
    public function the_link_is_in_their_sidebar(): void
    {
        $page = $this->actingAs($this->staff(User::ROLE_ACCOUNTING), 'staff')
            ->get('/admin/sales')
            ->assertOk();

        $page->assertSee('/admin/analytics', false);

        // And the screens they still cannot reach are not offered to them.
        $page->assertDontSee('/admin/dashboard', false);
    }

    #[Test]
    public function inventory_staff_are_not_offered_the_link(): void
    {
        $this->actingAs($this->staff(User::ROLE_INVENTORY_STAFF), 'staff')
            ->get('/admin/inventory')
            ->assertOk()
            ->assertDontSee('/admin/analytics', false);
    }

    /**
     * A deactivated account keeps its role and loses everything else, so the
     * permission has to read the flag rather than the role alone.
     */
    #[Test]
    public function a_deactivated_accountant_is_refused(): void
    {
        $user = $this->staff(User::ROLE_ACCOUNTING);
        $user->update(['is_active' => false]);

        $this->actingAs($user, 'staff')
            ->get('/admin/analytics')
            ->assertRedirect('/admin/login');
    }
}
