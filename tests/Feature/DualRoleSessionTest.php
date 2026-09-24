<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * A customer and a staff member signed in at once, in one browser.
 *
 * Two guards over the same table. The awkward part is not the guards, it is
 * everything built around a single session: regenerating the session id on
 * login, the one-session-per-account rule that reads that id, and a logout
 * that used to tear down the whole session. Each of those would quietly end
 * the other role's session, so each has a test here.
 */
class DualRoleSessionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'secret12345';

    private function customer(): User
    {
        $user = User::create([
            'email' => 'shopper@raney.test',
            'password' => self::PASSWORD,
            'full_name' => 'Liza Bautista',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

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

    private function admin(): User
    {
        return User::create([
            'email' => 'manager@raney.test',
            'password' => self::PASSWORD,
            'full_name' => 'Arnel Cruz',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function signInBoth(): array
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $this->postJson('/shop/login', ['email' => $customer->email, 'password' => self::PASSWORD])->assertOk();
        $this->postJson('/admin/login', ['email' => $admin->email, 'password' => self::PASSWORD])->assertOk();

        return [$customer, $admin];
    }

    #[Test]
    public function both_roles_can_hold_a_session_in_the_same_browser(): void
    {
        $this->signInBoth();

        $this->assertTrue(Auth::guard('web')->check(), 'The customer should still be signed in.');
        $this->assertTrue(Auth::guard('staff')->check(), 'The staff member should be signed in.');

        $this->assertSame('shopper@raney.test', Auth::guard('web')->user()->email);
        $this->assertSame('manager@raney.test', Auth::guard('staff')->user()->email);
    }

    #[Test]
    public function signing_in_second_does_not_evict_the_first(): void
    {
        [$customer, $admin] = $this->signInBoth();

        // Logging in regenerates the session id. Both accounts have to be
        // moved to it, or the single-session rule reads the older one as a
        // sign-in from another device and ends it.
        $this->assertSame(
            $customer->fresh()->current_session_id,
            $admin->fresh()->current_session_id,
            'Both accounts should point at the one session this browser has.'
        );
    }

    /**
     * Both guards occupied, without going through the login forms.
     *
     * The forms are covered above. Driving them again here would test the
     * test client's cookie handling rather than this application: it does not
     * carry the regenerated session cookie between calls the way a browser
     * does, so the second request arrives with a fresh, empty session.
     */
    private function actingAsBoth(): array
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer, 'web');
        $this->actingAs($admin, 'staff');

        return [$customer, $admin];
    }

    #[Test]
    public function each_side_still_reaches_its_own_pages(): void
    {
        $this->actingAsBoth();

        $this->get('/orders')->assertOk();
        $this->get('/admin/dashboard')->assertOk();
    }

    #[Test]
    public function signing_out_of_the_shop_leaves_the_back_office_signed_in(): void
    {
        $this->actingAsBoth();

        $this->postJson('/shop/logout')->assertOk();

        $this->assertFalse(Auth::guard('web')->check(), 'The customer should be signed out.');
        $this->assertTrue(Auth::guard('staff')->check(), 'The staff member should not have been.');
    }

    #[Test]
    public function signing_out_of_the_back_office_leaves_the_shop_signed_in(): void
    {
        $this->actingAsBoth();

        $this->post('/admin/logout');

        $this->assertFalse(Auth::guard('staff')->check(), 'The staff member should be signed out.');
        $this->assertTrue(Auth::guard('web')->check(), 'The customer should not have been.');
    }

    #[Test]
    public function a_staff_member_is_not_a_signed_in_customer(): void
    {
        $admin = $this->admin();

        $this->postJson('/admin/login', ['email' => $admin->email, 'password' => self::PASSWORD])->assertOk();

        // Separate guards, so the back office does not confer a shop session.
        $this->assertFalse(Auth::guard('web')->check());
        $this->get('/orders')->assertRedirect('/shop/login');
    }
}
