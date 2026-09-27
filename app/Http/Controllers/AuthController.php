<?php

namespace App\Http\Controllers;

use App\Support\PasswordPolicy;
use App\Http\Controllers\Concerns\StartsUserSessions;
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
    use StartsUserSessions;

    protected function loginRules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => PasswordPolicy::loginRules(),
        ];
    }

    protected function loginMessages(): array
    {
        return [
            'email.required' => 'Email address is required.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Email address must not exceed 150 characters.',
            'password.required' => 'Password is required.',
        ];
    }

    protected function customerRegistrationRules(): array
    {
        return [
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => PasswordPolicy::rules(),
            'full_name' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[A-Za-z][A-Za-z\s\'.-]*$/'],
            'phone' => ['required', 'string', 'regex:/^(09\d{9}|\+639\d{9})$/', 'unique:customer_profiles,phone'],
            'house_street' => ['required', 'string', 'min:5', 'max:160'],
            'barangay'     => ['required', 'string', 'max:100'],
            'city'         => ['required', 'string', 'max:100'],
            'province'     => ['required', 'string', 'max:100'],
            'postal_code'  => ['nullable', 'string', 'regex:/^\d{4}$/'],
        ];
    }

    protected function customerRegistrationMessages(): array
    {
        return PasswordPolicy::messages() + [
            'email.required' => 'Email address is required.',
            'email.email'   => 'Enter a valid email address.',
            'email.unique'  => 'That email address is already registered.',
            'full_name.required' => 'Full name is required.',
            'full_name.min' => 'Full name must be at least 2 characters.',
            'full_name.regex' => 'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'phone.required' => 'Phone number is required.',
            'phone.regex'   => 'Phone number must be a valid Philippine mobile number such as 09XXXXXXXXX or +639XXXXXXXXX.',
            'phone.unique'  => 'That phone number is already registered to another account.',
            'house_street.required' => 'Enter the house or building number and street.',
            'house_street.min'      => 'That looks too short to find. Include the number and street.',
            'barangay.required'     => 'Barangay is required. Couriers here rely on it.',
            'city.required'         => 'City or municipality is required.',
            'province.required'     => 'Province is required.',
            'postal_code.regex'     => 'A Philippine postal code is four digits, such as 1100.',
        ];
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
                'Previous active session was replaced by a new staff login.',
                'staff'
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

        /*
         * The hint matters more than it looks. The two doors take different
         * accounts, and typing a customer's at this one gives exactly the
         * same "invalid credentials" as a wrong password -- so somebody with
         * the right details in front of them concludes the details are wrong.
         *
         * Worded so it says nothing about whether the account exists: it is
         * shown on every failure, not only on the ones where it applies.
         */
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials. If this is a customer account, sign in at /shop/login instead.',
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

        // As above, the other way round. Shown on every failure, so it gives
        // nothing away about which accounts exist.
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials. If this is a staff account, sign in at /admin/login instead.',
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
            ] + $request->only(['house_street', 'barangay', 'city', 'province', 'postal_code']));

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
    /** Signing out of the shop. */
    public function logout(Request $request)
    {
        return $this->signOut($request, 'web');
    }

    /** Signing out of the back office, leaving any shop session alone. */
    public function staffLogout(Request $request)
    {
        return $this->signOut($request, 'staff');
    }

    /*
     * The guard is named rather than taken from the default driver: both
     * logout routes sit outside the middleware groups that choose a guard,
     * so the default here is always "web" and signing out of the back office
     * would have signed the customer out instead.
     */
    protected function signOut(Request $request, string $guard)
    {
        $user = Auth::guard($guard)->user();
        $userId = $user?->user_id;
        $label = $user ? "{$user->full_name} ({$user->email}) logged out" : 'User logged out';

        if ($user && $request->hasSession() && $user->current_session_id === $request->session()->getId()) {
            $user->forceFill([
                'current_session_id' => null,
            ])->save();
        }

        Auth::guard($guard)->logout();

        // The session is shared by both guards, so tearing it down is only
        // safe once nobody is left in it. Otherwise logging out of one role
        // would silently end the other.
        $othersStillSignedIn = collect(config('auth.session_guards'))
            ->reject(fn (string $name) => $name === $guard)
            ->contains(fn (string $name) => Auth::guard($name)->check());

        if ($request->hasSession()) {
            if ($othersStillSignedIn) {
                $request->session()->regenerateToken();
            } else {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
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
