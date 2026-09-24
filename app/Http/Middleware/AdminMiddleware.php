<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gates the back office as a whole. Which pages a signed-in staff member may
 * actually reach is decided per route by PermissionMiddleware.
 */
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Everything downstream -- controllers, views, the single-session
        // check -- resolves auth() to the staff guard from here, so no call
        // site has to name it.
        Auth::shouldUse('staff');

        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }
            return redirect('/admin/login');
        }

        $user = Auth::user();

        if (!$user->isStaff()) {
            $wasDeactivated = in_array($user->role, \App\Models\User::STAFF_ROLES, true) && !$user->is_active;

            $user->forceFill(['current_session_id' => null])->save();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $wasDeactivated
                ? 'Your account has been deactivated. Please contact an administrator.'
                : 'Unauthorized access';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 401);
            }

            return redirect('/admin/login')->with('error', $message);
        }

        return $next($request);
    }
}
