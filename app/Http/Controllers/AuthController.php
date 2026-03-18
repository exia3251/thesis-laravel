<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Admin login page
     */
    public function adminLoginPage()
    {
        return view('auth.admin-login');
    }

    /**
     * Customer login page
     */
    public function customerLoginPage()
    {
        return view('auth.customer-login');
    }

    /**
     * Admin login with rate limiting
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        // Rate limiting key
        $key = 'admin-login:' . $request->ip();

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        // Find user
        $user = User::where('username', $request->username)
                    ->whereIn('role', ['super_admin', 'admin', 'staff'])
                    ->first();

        // Verify password with Hash::check
        if ($user && Hash::check($request->password, $user->password)) {
            // Clear rate limiter on success
            RateLimiter::clear($key);
            
            Auth::login($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful'
            ]);
        }

        // Increment failed attempts
        RateLimiter::hit($key, 60); // Lock for 60 seconds after 5 attempts

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Customer login with rate limiting
     */
    public function customerLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        // Rate limiting key
        $key = 'customer-login:' . $request->ip();

        // Check if too many attempts
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        // Find user
        $user = User::where('username', $request->username)
                    ->where('role', 'customer')
                    ->first();

        // Verify password with Hash::check
        if ($user && Hash::check($request->password, $user->password)) {
            // Clear rate limiter on success
            RateLimiter::clear($key);
            
            Auth::login($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id' => $user->user_id,
                    'username' => $user->username,
                    'full_name' => $user->full_name
                ]
            ]);
        }

        // Increment failed attempts
        RateLimiter::hit($key, 60); // Lock for 60 seconds after 5 attempts

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Customer registration
     */
    public function customerRegister(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username|max:50',
            'password' => 'required|min:6|confirmed', // requires password_confirmation field
            'full_name' => 'required|max:100',
            'phone' => 'required|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // Create user - password auto-hashed by User model
            $user = User::create([
                'username' => $request->username,
                'password' => $request->password, // Auto-hashed by model
                'full_name' => $request->full_name,
                'role' => 'customer'
            ]);

            // Create customer profile
            CustomerProfile::create([
                'user_id' => $user->user_id,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Registration successful'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        Auth::logout();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
