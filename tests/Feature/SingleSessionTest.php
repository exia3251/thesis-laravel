<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * One session per account, now that a browser can hold two.
 *
 * Signing in somewhere else still ends the older session -- that rule is the
 * point of current_session_id. What changed is that the session it ends is
 * shared with the other guard, so ending it carelessly signs out a second,
 * unrelated account that happens to be open in the same browser.
 */
class SingleSessionTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'secret12345';
    private const ELSEWHERE = 'a-session-id-from-another-device';

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

    #[Test]
    public function signing_in_elsewhere_still_ends_the_older_session(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'web');

        // What a login on another device leaves behind.
        $customer->forceFill(['current_session_id' => self::ELSEWHERE])->save();

        $this->get('/orders')->assertRedirect('/shop/login');
        $this->assertFalse(Auth::guard('web')->check());
    }

    #[Test]
    public function it_says_so_in_the_activity_log(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'web');
        $customer->forceFill(['current_session_id' => self::ELSEWHERE])->save();

        $this->get('/orders');

        $this->assertSame(
            1,
            ActivityLog::where('user_id', $customer->user_id)->where('action', 'session_invalidated')->count()
        );
    }

    /*
     * These two pass for a weaker reason than they look: actingAs puts the
     * user straight onto the guard instance, so a guard survives even a
     * session that has been torn down underneath it. They pin the redirect
     * and the guard bookkeeping, and the session sharing itself was checked
     * against a running server with a real cookie jar -- where the first
     * version of this did sign the other account out.
     */
    #[Test]
    public function ending_one_account_does_not_end_the_other_in_the_same_browser(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer, 'web');
        $this->actingAs($admin, 'staff');

        // The customer account is used to sign in on another device. The
        // staff member sitting in the next tab has nothing to do with it.
        $customer->forceFill(['current_session_id' => self::ELSEWHERE])->save();

        $this->get('/orders')->assertRedirect('/shop/login');

        $this->assertFalse(Auth::guard('web')->check(), 'The customer should have been signed out.');
        $this->assertTrue(Auth::guard('staff')->check(), 'The staff member should not have been.');
    }

    #[Test]
    public function the_same_is_true_the_other_way_round(): void
    {
        $customer = $this->customer();
        $admin = $this->admin();

        $this->actingAs($customer, 'web');
        $this->actingAs($admin, 'staff');

        $admin->forceFill(['current_session_id' => self::ELSEWHERE])->save();

        $this->get('/admin/dashboard')->assertRedirect('/admin/login');

        $this->assertFalse(Auth::guard('staff')->check(), 'The staff member should have been signed out.');
        $this->assertTrue(Auth::guard('web')->check(), 'The customer should not have been.');
    }
}
