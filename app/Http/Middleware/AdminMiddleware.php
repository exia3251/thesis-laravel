<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
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

        // Deactivated admin (not super_admin) — force logout and destroy session
        if (!$user->isAdmin()) {
            $wasDeactivated = $user->role === 'admin' && !$user->is_active;

            $user->forceFill(['current_session_id' => null])->save();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $wasDeactivated
                        ? 'Your admin account has been deactivated. Please contact the super admin.'
                        : 'Unauthorized access'
                ], 401);
            }

            return redirect('/admin/login')->with(
                'error',
                $wasDeactivated
                    ? 'Your admin account has been deactivated. Please contact the super admin.'
                    : 'Unauthorized access.'
            );
        }

        return $next($request);
    }
}