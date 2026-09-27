<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * One live session per account, except for the logins everybody is given.
 *
 * The rule is right for a real customer: it is what stops a password being
 * passed around. Applied to a login handed to every survey respondent, it
 * means the second person to sign in throws the first one out mid-task --
 * which reads as the system crashing, during the very thing they were asked
 * to evaluate.
 */
class SharedDemoSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->app['env'] = 'local';
    }

    /** What the middleware sees when the account signed in somewhere else. */
    private function signedInElsewhere(User $user): void
    {
        $user->forceFill(['current_session_id' => 'a-session-belonging-to-somebody-else'])->save();
    }

    #[Test]
    public function two_people_can_share_the_demo_customer_login(): void
    {
        $john = User::where('email', 'john@example.com')->firstOrFail();
        $this->signedInElsewhere($john);

        $this->actingAs($john, 'web')->get('/orders')->assertOk();
    }

    #[Test]
    public function two_people_can_share_the_demo_staff_login(): void
    {
        $admin = User::where('email', 'admin@raney.test')->firstOrFail();
        $this->signedInElsewhere($admin);

        $this->actingAs($admin, 'staff')->get('/admin/dashboard')->assertOk();
    }

    #[Test]
    public function a_real_account_is_still_held_to_one_session(): void
    {
        // The protection that matters is untouched.
        $customer = User::create([
            'email' => 'someone.real@example.com',
            'password' => 'a-real-password',
            'full_name' => 'Someone Real',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->signedInElsewhere($customer);

        $this->actingAs($customer, 'web')->get('/orders')->assertRedirect('/shop/login');
    }

    #[Test]
    public function the_exemption_does_not_follow_the_project_into_production(): void
    {
        $this->app['env'] = 'production';

        $john = User::where('email', 'john@example.com')->firstOrFail();
        $this->signedInElsewhere($john);

        $this->actingAs($john, 'web')->get('/orders')->assertRedirect('/shop/login');
    }
}
