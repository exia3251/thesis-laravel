<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Concerns\StartsUserSessions;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Throwable;

/**
 * Signing in with Google.
 *
 * The whole of this class turns on one decision: an account is found by its
 * Google subject id, not by the email address Google reports. A subject id
 * belongs to one Google account for ever. An email address can be changed
 * here, and can be reassigned by a provider there, so matching on it would
 * mean whoever came to control a mailbox could sign in as whoever used it
 * before. The address is used once, to connect an existing account to a
 * Google identity the first time, and even then only when Google states it
 * has verified that address.
 */
class GoogleAuthController extends Controller
{
    use StartsUserSessions;

    /** Hand the visitor to Google. */
    public function redirect(Request $request)
    {
        if (!$this->configured()) {
            return redirect('/shop/login')->with('error', 'Google Sign-In is not set up on this installation.');
        }

        return Socialite::driver('google')->redirectUrl($this->callbackUrl())->redirect();
    }

    public function callback(Request $request)
    {
        $loginPage = '/shop/login';

        if (!$this->configured()) {
            return redirect($loginPage)->with('error', 'Google Sign-In is not set up on this installation.');
        }

        try {
            /** @var GoogleUser $googleUser */
            $googleUser = Socialite::driver('google')->redirectUrl($this->callbackUrl())->user();
        } catch (Throwable $e) {
            // Covers the visitor pressing cancel as well as a genuine fault,
            // so the message cannot accuse them of something they did on purpose.
            Log::warning('Google sign-in did not complete.', ['error' => $e->getMessage()]);

            return redirect($loginPage)->with('error', 'Google sign-in did not complete. Please try again.');
        }

        $user = $this->resolve($googleUser, $failure);

        if (!$user) {
            return redirect($loginPage)->with('error', $failure);
        }

        /*
         * Google Sign-In is for customers. A staff account reaching here
         * would be signed in on the shop's guard, and the back office asks
         * the staff guard, so it would bounce them straight back to the
         * login page with nothing to explain why.
         *
         * Refusing outright is also the safer rule: who may enter the back
         * office is something an administrator decides by issuing a
         * password, not something a Google account confers.
         */
        if ($user->isStaff()) {
            Log::warning('A staff account attempted Google sign-in.', ['user_id' => $user->user_id]);

            return redirect('/admin/login')->with(
                'error',
                'Staff accounts sign in with their email and password, not with Google.'
            );
        }

        $this->startSession(
            $request,
            $user,
            'customer_login',
            "{$user->full_name} signed in with Google elsewhere, so the earlier session was ended.",
            'web'
        );

        // Someone who has just been created through Google has nothing but
        // a name and an email address. Sending them to the catalogue means
        // discovering at checkout that they cannot order; sending them here
        // means one short form and then they can.
        if ($user->wasRecentlyCreated && $user->isCustomer()) {
            return redirect('/profile')
                ->with('profile_prompt', 'Welcome. Add your phone number and delivery address and you are ready to order.');
        }

        return redirect($user->homePath());
    }

    /**
     * Finds or creates the account behind a Google identity.
     *
     * @param  string|null  $failure  set to the reason when nothing is returned
     */
    private function resolve(GoogleUser $googleUser, ?string &$failure): ?User
    {
        $googleId = (string) $googleUser->getId();
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        // 1. Seen before. Nothing else needs checking, because the subject id
        //    is what identifies the account.
        $user = User::where('google_id', $googleId)->first();

        if ($user) {
            return $this->usable($user, $failure);
        }

        if ($email === '') {
            $failure = 'Google did not share an email address, so we could not sign you in.';

            return null;
        }

        // 2. An address we already hold. Linking is only safe when Google
        //    states it has verified the address; without that, anyone able to
        //    set an unverified address at a provider could claim the account.
        if (!($googleUser->user['email_verified'] ?? false)) {
            $failure = 'Google has not verified that email address, so it cannot be linked to an account here.';

            return null;
        }

        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing) {
            if ($existing->trashed()) {
                $failure = 'That account has been archived. Please contact us if it should be restored.';

                return null;
            }

            // Checked before anything is written. Linking and then refusing
            // left a deactivated account carrying a Google link it had never
            // successfully used, and a log entry saying it had.
            if (!$this->usable($existing, $failure)) {
                return null;
            }

            $existing->forceFill([
                'google_id' => $googleId,
                // Google has just proved the address, so an account that had
                // been waiting on a confirmation email no longer needs one.
                'email_verified_at' => $existing->email_verified_at ?? now(),
            ])->save();

            ActivityLog::logAction(
                $existing->user_id,
                'google_account_linked',
                "{$existing->full_name} ({$existing->email}) linked Google Sign-In to their existing account."
            );

            return $existing;
        }

        // 3. Nobody we know. New customers only -- a staff account is
        //    something an administrator creates deliberately, never something
        //    that appears because somebody signed in.
        $user = User::create([
            'email' => $email,
            'google_id' => $googleId,
            'full_name' => $this->nameFrom($googleUser, $email),
            'role' => User::ROLE_CUSTOMER,
            'password' => null,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        ActivityLog::logAction(
            $user->user_id,
            'customer_registered',
            "{$user->full_name} ({$user->email}) created an account with Google Sign-In."
        );

        return $user;
    }

    /** Accounts an administrator has switched off cannot be signed into. */
    private function usable(User $user, ?string &$failure): ?User
    {
        if (!$user->is_active) {
            $failure = 'That account has been deactivated. Please contact an administrator.';

            return null;
        }

        return $user;
    }

    /**
     * The account rules elsewhere want at least two words, so a single-word
     * Google profile name is padded rather than rejected at the door.
     */
    private function nameFrom(GoogleUser $googleUser, string $email): string
    {
        $name = trim((string) $googleUser->getName());

        if ($name === '') {
            $name = str_replace(['.', '_', '-'], ' ', str($email)->before('@')->toString());
            $name = ucwords(trim($name));
        }

        return str_word_count($name) >= 2 ? mb_substr($name, 0, 100) : mb_substr($name . ' Customer', 0, 100);
    }

    private function configured(): bool
    {
        return config('services.google.enabled')
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    /**
     * Point the callback at the address this visitor actually arrived on.
     *
     * Google matches the redirect character for character, so a fixed one in
     * .env is right for exactly one address and wrong everywhere else. It
     * said port 8000 while the server ran on 8123, which broke sign-in
     * silently -- nothing complains until somebody clicks the button and
     * lands on a Google error page.
     *
     * Built from the request instead, so localhost works while developing and
     * the tunnel address works while the system is being demonstrated,
     * without editing anything in between. Both still have to be registered
     * on the OAuth client; this only stops the wrong one being sent.
     *
     * GOOGLE_REDIRECT_URI still wins when it is set, for an installation that
     * wants the callback pinned to one address.
     */
    private function callbackUrl(): string
    {
        return (string) (config('services.google.redirect') ?: url('/auth/google/callback'));
    }
}
