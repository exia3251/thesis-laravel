<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    protected function loginRules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    protected function loginMessages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username must not exceed 30 characters.',
            'username.regex' => 'Username must start with a letter and may only contain letters, numbers, dots, underscores, or hyphens.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    protected function customerRegistrationRules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:30', 'unique:users,username', 'regex:/^[A-Za-z][A-Za-z0-9._-]*$/'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'full_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\'.-]*$/'],
            'phone' => ['required', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function customerRegistrationMessages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.min' => 'Username must be at least 3 characters.',
            'username.max' => 'Username must not exceed 30 characters.',
            'username.unique' => 'That username is already taken.',
            'username.regex' => 'Username must start with a letter and may only contain letters, numbers, dots, underscores, or hyphens.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'full_name.required' => 'Full name is required.',
            'full_name.min' => 'Full name must be at least 2 characters.',
            'full_name.regex' => 'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be a valid Philippine mobile number such as 09XXXXXXXXX or +639XXXXXXXXX.',
            'email.email' => 'Email address must be valid.',
            'address.required' => 'Address is required.',
            'address.min' => 'Address must be at least 10 characters long.',
        ];
    }

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
        $request->validate($this->loginRules(), $this->loginMessages());

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
                    ->whereIn('role', ['super_admin', 'admin'])
                    ->first();

        // Verify password with Hash::check
        if ($user && Hash::check($request->password, $user->password)) {

            // Block deactivated admin accounts (super_admin is exempt)
            if ($user->role === 'admin' && !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your admin account has been deactivated. Please contact the super admin.'
                ], 403);
            }

            // Clear rate limiter on success
            RateLimiter::clear($key);
            $previousSessionId = $user->current_session_id;

            Auth::login($user);

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user->forceFill([
                'current_session_id' => $request->session()->getId(),
            ])->save();

            ActivityLog::logAction($user->user_id, 'admin_login', 'Admin logged in', $request->ip());

            if ($previousSessionId && $previousSessionId !== $request->session()->getId()) {
                ActivityLog::logAction(
                    $user->user_id,
                    'single_session_replaced',
                    'Previous active session was replaced by a new admin login.',
                    $request->ip()
                );
            }
            
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
        $request->validate($this->loginRules(), $this->loginMessages());

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

            // Reject deactivated accounts before creating a session
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 403);
            }

            // Clear rate limiter on success
            RateLimiter::clear($key);
            $previousSessionId = $user->current_session_id;

            Auth::login($user);

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            $user->forceFill([
                'current_session_id' => $request->session()->getId(),
            ])->save();

            ActivityLog::logAction($user->user_id, 'customer_login', 'Customer logged in', $request->ip());

            if ($previousSessionId && $previousSessionId !== $request->session()->getId()) {
                ActivityLog::logAction(
                    $user->user_id,
                    'single_session_replaced',
                    'Previous active session was replaced by a new customer login.',
                    $request->ip()
                );
            }
            
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
        $request->validate($this->customerRegistrationRules(), $this->customerRegistrationMessages());

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
    public function logout(Request $request)
    {
        $user = auth()->user();
        $userId = $user?->user_id;

        if ($user && $request->hasSession() && $user->current_session_id === $request->session()->getId()) {
            $user->forceFill([
                'current_session_id' => null,
            ])->save();
        }

        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($userId) {
            ActivityLog::logAction($userId, 'logout', 'User logged out', $request->ip());
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}