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
}
