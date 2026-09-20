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
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
        ];
    }

    protected function loginMessages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Email address must not exceed 150 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    protected function customerRegistrationRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'full_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\'.-]*$/'],
            'phone' => ['required', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/', 'unique:customer_profiles,phone'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function customerRegistrationMessages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email'   => 'Enter a valid email address.',
            'email.unique'  => 'That email address is already registered.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'full_name.required' => 'Full name is required.',
            'full_name.min' => 'Full name must be at least 2 characters.',
            'full_name.regex' => 'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'phone.required' => 'Phone number is required.',
            'phone.regex'   => 'Phone number must be a valid Philippine mobile number such as 09XXXXXXXXX or +639XXXXXXXXX.',
            'phone.unique'  => 'That phone number is already registered to another account.',
            'address.required' => 'Address is required.',
            'address.min' => 'Address must be at least 10 characters long.',
        ];
    }

    /**
     * Opens a session for an account that has already been authenticated,
     * replacing whatever session it previously held.
     */
    protected function startSession(Request $request, User $user, string $logAction, string $replacedNote): void
    {
        $previousSessionId = $user->current_session_id;

        Auth::login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->forceFill([
            'current_session_id' => $request->session()->getId(),
        ])->save();

        ActivityLog::logAction(
            $user->user_id,
            $logAction,
            "{$user->full_name} ({$user->email}) logged in as {$user->roleLabel()}"
        );

        if ($previousSessionId && $previousSessionId !== $request->session()->getId()) {
            ActivityLog::logAction($user->user_id, 'single_session_replaced', $replacedNote);
        }
    }

    /**
     * Staff login with rate limiting.
     */
    public function adminLogin(Request $request)
    {
        $request->validate($this->loginRules(), $this->loginMessages());

        $key = 'admin-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        $user = User::where('email', $request->email)
                    ->whereIn('role', User::STAFF_ROLES)
                    ->first();

        if ($user && $user->password && Hash::check($request->password, $user->password)) {

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact an administrator.'
                ], 403);
            }

            RateLimiter::clear($key);

            $this->startSession(
                $request,
                $user,
                'admin_login',
                'Previous active session was replaced by a new staff login.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'role' => $user->role,
                    'redirect' => $user->homePath(),
                ],
            ]);
        }

        RateLimiter::hit($key, 60);

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Customer login with rate limiting.
     */
    public function customerLogin(Request $request)
    {
        $request->validate($this->loginRules(), $this->loginMessages());

        $key = 'customer-login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        $user = User::where('email', $request->email)
                    ->where('role', User::ROLE_CUSTOMER)
                    ->first();

        if ($user && $user->password && Hash::check($request->password, $user->password)) {

            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact support.'
                ], 403);
            }

            RateLimiter::clear($key);

            $this->startSession(
                $request,
                $user,
                'customer_login',
                'Previous active session was replaced by a new customer login.'
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'redirect' => $user->homePath(),
                ]
            ]);
        }

        RateLimiter::hit($key, 60);

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Customer registration.
     */
    public function customerRegister(Request $request)
    {
        $request->validate($this->customerRegistrationRules(), $this->customerRegistrationMessages());

        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $request->email,
                'password' => $request->password, // hashed by the model cast
                'full_name' => $request->full_name,
                'role' => User::ROLE_CUSTOMER,
            ]);

            CustomerProfile::create([
                'user_id' => $user->user_id,
                'phone' => $request->phone,
                'address' => $request->address,
            ]);

            DB::commit();

            // Verification is what later makes linking a Google sign-in to this
            // account safe, so it is requested straight away.
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Verification mail failed.', [
                    'user_id' => $user->user_id,
                    'error' => $e->getMessage(),
                ]);
            }

            ActivityLog::logAction(
                $user->user_id,
                'customer_registered',
                "New customer registered: {$user->full_name} ({$user->email})"
            );

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Check your email for a verification link.',
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
     * Logout.
     */
    public function logout(Request $request)
    {
        $user = auth()->user();
        $userId = $user?->user_id;
        $label = $user ? "{$user->full_name} ({$user->email}) logged out" : 'User logged out';

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
            ActivityLog::logAction($userId, 'logout', $label);
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
