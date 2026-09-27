<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One live session per account.
 *
 * An account signed in somewhere else leaves a different id on the row than
 * the session holding it here, and that is the signal to end this one.
 *
 * The session is shared with the other guard, though, so ending it is done
 * carefully: this signs out the one account, and only tears the session down
 * once nobody is left in it. Invalidating outright also signed out whoever
 * was in the next tab, who had nothing to do with it.
 */
class EnforceSingleSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Whichever guard the route's own middleware selected.
        $guard = Auth::getDefaultDriver();

        if (!Auth::guard($guard)->check()) {
            return $next($request);
        }

        $user = Auth::guard($guard)->user();

        /*
         * The demonstration accounts are shared on purpose.
         *
         * One live session per account is right for a real customer: it is
         * what stops a password being passed around. But a survey hands the
         * same login to everybody who follows the link, and with this rule
         * applied to it the second respondent to sign in throws the first one
         * out mid-task -- which reads as the system crashing, in the middle of
         * the thing they were asked to evaluate.
         *
         * Only while the environment is local, and only for the accounts the
         * seeder creates for that purpose. Every real account keeps the rule.
         */
        if ($this->isSharedDemoAccount($user)) {
            return $next($request);
        }

        $currentSessionId = $request->session()->getId();

        if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
            ActivityLog::logAction(
                $user->user_id,
                'session_invalidated',
                'Session ended because the account was used to sign in on another device.'
            );

            Auth::guard($guard)->logout();

            $othersStillSignedIn = collect(config('auth.session_guards'))
                ->reject(fn (string $name) => $name === $guard)
                ->contains(fn (string $name) => Auth::guard($name)->check());

            if ($othersStillSignedIn) {
                $request->session()->forget('password_hash_' . $guard);
            } else {
                $request->session()->invalidate();
            }

            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account was logged in on another device. Please sign in again.',
                ], 401);
            }

            return $guard === 'staff'
                ? redirect('/admin/login')
                : redirect('/shop/login');
        }

        return $next($request);
    }

    /**
     * Whether this is one of the logins handed out with the survey link.
     *
     * Gated on the environment as well as the address, so the exemption
     * cannot follow the project into a real deployment even if the accounts
     * somehow do.
     */
    private function isSharedDemoAccount($user): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        return in_array(
            mb_strtolower((string) $user->email),
            array_map('mb_strtolower', (array) config('business.shared_demo_accounts', [])),
            true
        );
    }
}
