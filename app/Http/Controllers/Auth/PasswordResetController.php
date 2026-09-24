<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

/**
 * Forgetting a password, and getting back in.
 *
 * A customer who forgot theirs had no way back at all: no reset, and staff
 * cannot read a password to tell them what it was. The only route was asking
 * an administrator to type a new one into the Users screen, which means
 * somebody else choosing your password and knowing it.
 *
 * Two things this deliberately does not do:
 *
 * It never says whether an address is registered. "No account with that
 * email" turns the form into a way of testing which of a list of addresses
 * shops here, so the answer is the same either way and the difference is
 * only in the log.
 *
 * It does not sign anybody in at the end. The link proves control of the
 * mailbox, which is enough to set a password, and asking for that password
 * once more at the ordinary sign-in costs a moment and proves it arrived.
 */
class PasswordResetController extends Controller
{
    /**
     * The address the emailed link points at.
     *
     * Lives here rather than in the service provider that sends the mail, so
     * the route and the link that opens it are written down together.
     */
    public static function linkFor($notifiable, string $token): string
    {
        return url('/shop/reset-password/' . $token . '?email=' . urlencode($notifiable->getEmailForPasswordReset()));
    }

    public function request()
    {
        return view('customer.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(
            ['email' => ['required', 'string', 'email', 'max:150']],
            ['email.email' => 'That does not look like an email address.']
        );

        $email = strtolower(trim($request->input('email')));
        $user = User::where('email', $email)->first();

        /*
         * Archived accounts are already hidden by the model's scope. A
         * deactivated one is not, and a link that lets somebody set a
         * password they still cannot sign in with is a worse answer than
         * silence, so it is treated the same as an address we do not hold.
         */
        if ($user && $user->is_active) {
            try {
                $status = Password::sendResetLink(['email' => $email]);

                if ($status === Password::RESET_THROTTLED) {
                    return back()->with('sent', $this->sentMessage())->withInput();
                }

                ActivityLog::logAction($user->user_id, 'password_reset_requested', "{$user->full_name} ({$email}) asked for a password reset link.");
            } catch (\Throwable $e) {
                // Sending can fail for reasons the customer cannot act on, and
                // saying so would also confirm the address exists.
                Log::error('Password reset mail failed.', ['email' => $email, 'error' => $e->getMessage()]);
            }
        } else {
            Log::info('Password reset asked for an address we do not hold, or one that is switched off.', ['email' => $email]);
        }

        return back()->with('sent', $this->sentMessage());
    }

    /** Same words whether or not the address is one of ours. */
    private function sentMessage(): string
    {
        return 'If that address belongs to an account here, a link to reset the password is on its way to it. '
            . 'It expires in ' . config('auth.passwords.users.expire') . ' minutes.';
    }

    public function edit(Request $request, string $token)
    {
        return view('customer.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => PasswordPolicy::rules(),
        ], PasswordPolicy::messages());

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($request) {
                /*
                 * No remember_token here, though Laravel's own reset writes
                 * one: this project's users table was written by hand and
                 * never had that column, so there are no remember-me cookies
                 * to invalidate. The session id below does that job.
                 */
                $user->forceFill([
                    'password' => $password,
                    /*
                     * Whoever knew the old password is signed out. A reset is
                     * most often asked for because somebody else has the
                     * account, and leaving their session alive would hand it
                     * straight back to them.
                     */
                    'current_session_id' => null,
                ])->save();

                ActivityLog::logAction(
                    $user->user_id,
                    'password_reset',
                    "{$user->full_name} ({$user->email}) set a new password from an emailed link."
                );

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect('/shop/login')->with('status', 'Your password has been changed. Sign in with it below.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $this->failure($status)]);
    }

    /**
     * Laravel answers with one of a handful of statuses. They are turned into
     * sentences here, because "passwords.token" is not one.
     */
    private function failure(string $status): string
    {
        return match ($status) {
            Password::INVALID_TOKEN => 'That link has already been used, or it has expired. Ask for a new one.',
            Password::INVALID_USER => 'That link has already been used, or it has expired. Ask for a new one.',
            Password::RESET_THROTTLED => 'That was just asked for. Wait a minute and try again.',
            default => 'That link could not be used. Ask for a new one.',
        };
    }
}
