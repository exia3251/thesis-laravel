<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Getting back into an account whose password has been forgotten.
 *
 * The parts worth pinning down are the ones that are invisible when they go
 * wrong: that the form cannot be used to find out who shops here, that a
 * link cannot be used twice, and that resetting actually ends the session of
 * whoever was using the account before.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'oldpassword123';

    private function customer(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'email' => 'shopper@raney.test',
            'password' => self::PASSWORD,
            'full_name' => 'Liza Bautista',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ], $overrides));

        $user->markEmailAsVerified();

        return $user;
    }

    /** The token as the customer receives it, out of the email. */
    private function tokenFor(User $user): string
    {
        $token = null;

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        return $token;
    }

    #[Test]
    public function the_form_is_reachable_from_the_sign_in_page(): void
    {
        $this->get('/shop/login')->assertOk()->assertSee('/shop/forgot-password');
        $this->get('/shop/forgot-password')->assertOk()->assertSee('Forgotten your password?');
    }

    #[Test]
    public function asking_for_a_link_sends_one(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/shop/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('sent');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function an_address_we_do_not_hold_is_answered_exactly_the_same_way(): void
    {
        Notification::fake();

        $known = $this->post('/shop/forgot-password', ['email' => $this->customer()->email]);
        $unknown = $this->post('/shop/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame(
            $known->getSession()->get('sent'),
            $unknown->getSession()->get('sent'),
            'A different answer turns this form into a way of testing which addresses shop here.'
        );

        Notification::assertCount(1);
    }

    #[Test]
    public function a_deactivated_account_is_sent_nothing(): void
    {
        Notification::fake();
        $user = $this->customer(['is_active' => false]);

        $this->post('/shop/forgot-password', ['email' => $user->email])->assertSessionHas('sent');

        // Setting a password they still cannot sign in with helps nobody.
        Notification::assertNothingSent();
    }

    #[Test]
    public function an_archived_account_is_sent_nothing(): void
    {
        Notification::fake();
        $user = $this->customer();
        $user->delete();

        $this->post('/shop/forgot-password', ['email' => $user->email])->assertSessionHas('sent');

        Notification::assertNothingSent();
    }

    #[Test]
    public function the_link_sets_the_password(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/shop/forgot-password', ['email' => $user->email]);

        $this->post('/shop/reset-password', [
            'token' => $this->tokenFor($user),
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect('/shop/login')->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
        $this->assertFalse(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    #[Test]
    public function resetting_signs_out_whoever_was_using_the_account(): void
    {
        Notification::fake();
        $user = $this->customer();

        // Somebody is signed in on the old password right now.
        $user->forceFill(['current_session_id' => 'a-session-somebody-else-holds'])->save();

        $this->post('/shop/forgot-password', ['email' => $user->email]);
        $this->post('/shop/reset-password', [
            'token' => $this->tokenFor($user),
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect('/shop/login');

        $this->assertNull(
            $user->fresh()->current_session_id,
            'A reset is usually asked for because somebody else has the account.'
        );
    }

    #[Test]
    public function a_link_cannot_be_used_twice(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/shop/forgot-password', ['email' => $user->email]);
        $token = $this->tokenFor($user);

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ];

        $this->post('/shop/reset-password', $payload)->assertRedirect('/shop/login');

        $this->post('/shop/reset-password', $payload + ['password' => 'third-password-attempt'])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    #[Test]
    public function a_made_up_token_is_refused(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/shop/reset-password', [
            'token' => 'not-a-token-anybody-issued',
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    #[Test]
    public function one_persons_token_does_not_open_another_persons_account(): void
    {
        Notification::fake();
        $liza = $this->customer();
        $bert = $this->customer(['email' => 'bert@raney.test', 'full_name' => 'Bert Navarro']);

        $this->post('/shop/forgot-password', ['email' => $liza->email]);

        $this->post('/shop/reset-password', [
            'token' => $this->tokenFor($liza),
            'email' => $bert->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::PASSWORD, $bert->fresh()->password));
    }

    #[Test]
    public function the_new_password_answers_to_the_same_policy_as_every_other(): void
    {
        Notification::fake();
        $user = $this->customer();
        $this->post('/shop/forgot-password', ['email' => $user->email]);
        $token = $this->tokenFor($user);

        // Too short.
        $this->post('/shop/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        // The two do not match.
        $this->post('/shop/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-different-password',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->password));
    }

    #[Test]
    public function a_customer_who_signed_up_with_google_can_set_a_password_this_way(): void
    {
        Notification::fake();

        $user = User::create([
            'email' => 'google@raney.test',
            'full_name' => 'Marites Galang',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
        $user->markEmailAsVerified();

        $this->post('/shop/forgot-password', ['email' => $user->email]);

        $this->post('/shop/reset-password', [
            'token' => $this->tokenFor($user),
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertRedirect('/shop/login');

        $this->assertTrue(Hash::check('a-brand-new-password', $user->fresh()->password));
    }

    #[Test]
    public function the_new_password_signs_them_in(): void
    {
        Notification::fake();
        $user = $this->customer();

        $this->post('/shop/forgot-password', ['email' => $user->email]);
        $this->post('/shop/reset-password', [
            'token' => $this->tokenFor($user),
            'email' => $user->email,
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ]);

        $this->postJson('/shop/login', [
            'email' => $user->email,
            'password' => 'a-brand-new-password',
        ])->assertOk();

        $this->assertAuthenticatedAs($user->fresh());
    }

    #[Test]
    public function somebody_already_signed_in_is_sent_to_their_own_pages(): void
    {
        $user = $this->customer();

        // They have the Account page for this; the form would be a detour.
        $this->actingAs($user)->get('/shop/forgot-password')->assertRedirect();
    }
}
