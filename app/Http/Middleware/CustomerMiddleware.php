<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please login first'
                ], 401);
            }
            return redirect('/shop/login');
        }

        $user = Auth::user();

        // Account is deactivated — force logout and destroy session
        if (!$user->isCustomer()) {
            $wasDeactivated = $user->role === 'customer' && !$user->is_active;

            $user->forceFill(['current_session_id' => null])->save();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $wasDeactivated
                        ? 'Your account has been deactivated. Please contact support.'
                        : 'Please login first'
                ], 401);
            }

            return redirect('/shop/login')->with(
                'error',
                $wasDeactivated
                    ? 'Your account has been deactivated. Please contact support.'
                    : 'Please login first.'
            );
        }

        return $next($request);
    }
}