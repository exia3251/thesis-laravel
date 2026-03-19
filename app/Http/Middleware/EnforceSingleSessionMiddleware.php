<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceSingleSessionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        $currentSessionId = $request->session()->getId();

        if ($user->current_session_id && $user->current_session_id !== $currentSessionId) {
            ActivityLog::logAction(
                $user->user_id,
                'session_invalidated',
                'Session ended because the account was used to sign in on another device.',
                $request->ip()
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account was logged in on another device. Please sign in again.',
                ], 401);
            }

            return $user->isCustomer()
                ? redirect('/shop/login')
                : redirect('/admin/login');
        }

        return $next($request);
    }
}
