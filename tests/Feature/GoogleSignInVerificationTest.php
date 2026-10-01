<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Why an account made with Google is already verified.
 *
 * A verification email exists to prove the person asking for the account can
 * read mail at that address. Signing in with Google proves the same thing
 * more strongly: they did not merely receive a message there, they hold the
 * account the address belongs to, and Google says it has checked it. Sending
 * a confirmation afterwards would ask somebody to prove what has just been
 * proven, and an unopened one would leave a real customer unable to order.
 *
 * The strength of that argument rests entirely on Google's own
 * email_verified flag. These tests hold the system to refusing the address
 * when that flag is absent -- because without it the address is only a
 * string somebody typed at a provider, and accepting it would hand over any
 * account that happens to use it.
 */
class GoogleSignInVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => null,
        ]);
    }

    /** Stands in for the person Google hands back. */
    private function googleReturns(
        string $id,
        string $email,
        bool $emailVerified = true,
        string $name = 'Jane Dela Cruz',
    ): void {
        $googleUser = Mockery::mock(GoogleUser::class);
        $googleUser->shouldReceive('getId')->andReturn($id);
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn($name);
        $googleUser->user = ['email_verified' => $emailVerified];

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('redirectUrl')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    #[Test]
    public function an_account_made_with_google_needs_no_confirmation_email(): void
    {
        Mail::fake();

        $this->googleReturns('google-sub-1', 'newcustomer@example.com');

        $this->get('/auth/google/callback')->assertRedirect('/profile');

        $user = User::where('email', 'newcustomer@example.com')->first();

        $this->assertNotNull($user, 'No account was created.');
        $this->assertNotNull(
            $user->email_verified_at,
            'The account was left unverified, so a customer who proved the address to '
            . 'Google would still be waiting on an email to order.'
        );

        // Nothing was sent, which is the point: there is nothing left to ask.
        Mail::assertNothingSent();
    }

    /**
     * The flag the whole argument rests on. Without it the address is just a
     * string somebody set at a provider.
     */
    #[Test]
    public function an_address_google_has_not_verified_is_refused_outright(): void
    {
        $this->googleReturns('google-sub-2', 'unverified@example.com', emailVerified: false);

        $this->get('/auth/google/callback')->assertRedirect('/shop/login');

        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
    }

    /**
     * The attack the flag prevents: somebody sets an address they do not own
     * at a provider that does not check, and signs in as whoever uses it here.
     */
    #[Test]
    public function an_unverified_address_cannot_take_over_an_existing_account(): void
    {
        $victim = User::create([
            'email' => 'real.customer@example.com',
            'password' => 'secret12345',
            'full_name' => 'Real Customer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        $this->googleReturns('attacker-sub', 'real.customer@example.com', emailVerified: false);

        $this->get('/auth/google/callback')->assertRedirect('/shop/login');

        $this->assertGuest('web');
        $this->assertNull(
            $victim->fresh()->google_id,
            'An unverified address was allowed to attach itself to somebody else\'s account.'
        );
    }

    #[Test]
    public function an_account_waiting_on_a_confirmation_stops_waiting_once_google_vouches(): void
    {
        $user = User::create([
            'email' => 'waiting@example.com',
            'password' => 'secret12345',
            'full_name' => 'Waiting Customer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => null])->save();

        $this->googleReturns('google-sub-3', 'waiting@example.com');

        $this->get('/auth/google/callback');

        $this->assertNotNull(
            $user->fresh()->email_verified_at,
            'Google proved the address and the account is still waiting on an email.'
        );
    }

    /**
     * An account is found by its Google subject id, never by the address.
     * A subject id belongs to one Google account for ever; an address can be
     * changed here and reassigned there.
     */
    #[Test]
    public function returning_with_the_same_google_account_does_not_make_a_second_one(): void
    {
        $this->googleReturns('google-sub-4', 'repeat@example.com');
        $this->get('/auth/google/callback');

        $this->assertSame(1, User::where('email', 'repeat@example.com')->count());

        // Same person, same Google account, signing in again later.
        Mockery::close();
        $this->googleReturns('google-sub-4', 'repeat@example.com');
        $this->get('/auth/google/callback');

        $this->assertSame(
            1,
            User::where('email', 'repeat@example.com')->count(),
            'Signing in a second time created a duplicate account.'
        );
    }

    #[Test]
    public function the_account_it_creates_is_always_a_customer(): void
    {
        $this->googleReturns('google-sub-5', 'whoever@example.com');

        $this->get('/auth/google/callback');

        $this->assertSame(
            User::ROLE_CUSTOMER,
            User::where('email', 'whoever@example.com')->first()->role,
            'Signing in with Google produced something other than a customer.'
        );
    }
}
