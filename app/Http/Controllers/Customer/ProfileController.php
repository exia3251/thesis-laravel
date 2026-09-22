<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display profile page
     */
    public function index()
    {
        return view('customer.profile');
    }

    /**
     * Get profile
     */
    public function getProfile()
    {
        $profile = CustomerProfile::with('user')
            ->where('user_id', auth()->id())
            ->first();

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    /**
     * Update profile. The email address lives on the user record because it
     * is the login identifier; phone and address stay on the profile.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $profile = CustomerProfile::where('user_id', $user->user_id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $request->validate([
            'phone'   => [
                'required', 'string',
                'regex:/^(09\d{9}|\+639\d{9})$/',
                'unique:customer_profiles,phone,' . $profile->profile_id . ',profile_id',
            ],
            'full_name' => [
                'required', 'string', 'min:2', 'max:100',
                'regex:/^[A-Za-z][A-Za-z\s\'.-]*$/',
            ],
            'email'   => [
                'required', 'email', 'max:150',
                'unique:users,email,' . $user->user_id . ',user_id',
            ],
            'house_street' => ['required', 'string', 'min:5', 'max:160'],
            'barangay'     => ['required', 'string', 'max:100'],
            'city'         => ['required', 'string', 'max:100'],
            'province'     => ['required', 'string', 'max:100'],
            'postal_code'  => ['nullable', 'string', 'regex:/^\d{4}$/'],
        ], [
            'full_name.required' => 'Your name is required. It appears on your receipts.',
            'full_name.regex'    => 'A name may use letters, spaces, apostrophes, periods and hyphens.',
            'phone.required' => 'Phone number is required.',
            'phone.regex'    => 'Enter a valid PH number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
            'phone.unique'   => 'That phone number is already registered to another account.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Enter a valid email address.',
            'email.unique'   => 'That email address is already registered to another account.',
            'house_street.required' => 'Enter the house or building number and street.',
            'house_street.min'      => 'That looks too short to find. Include the number and street.',
            'barangay.required'     => 'Barangay is required. Couriers here rely on it.',
            'city.required'         => 'City or municipality is required.',
            'province.required'     => 'Province is required.',
            'postal_code.regex'     => 'A Philippine postal code is four digits, such as 1100.',
        ]);

        $emailChanged = $request->email !== $user->email;

        DB::transaction(function () use ($request, $user, $profile, $emailChanged) {
            $profile->update($request->only(['phone', 'house_street', 'barangay', 'city', 'province', 'postal_code']));

            $changes = ['full_name' => $request->full_name];

            if ($emailChanged) {
                // A new address is unproven until it has been confirmed, and
                // ordering is gated on that, so it goes back to unverified
                // rather than inheriting the old address's standing.
                $changes['email'] = $request->email;
                $changes['email_verified_at'] = null;
            }

            $user->forceFill($changes)->save();
        });

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        ActivityLog::logAction(
            $user->user_id,
            'profile_updated',
            "Customer {$user->full_name} ({$user->email}) updated their profile."
        );

        return response()->json([
            'success' => true,
            'message' => $emailChanged
                ? 'Saved. Confirm your new email address using the link we just sent.'
                : 'Saved.',
            'data' => $profile->fresh('user'),
        ]);
    }

    /**
     * Change password with Hash
     */
    public function changePassword(Request $request)
    {
        $user = User::findOrFail(auth()->id());

        // An account created through Google Sign-In has no password yet, so
        // there is nothing for it to confirm the first time one is set.
        $hasPassword = filled($user->password);

        $request->validate([
            'current_password' => [$hasPassword ? 'required' : 'nullable', 'string', 'max:255'],
            'new_password' => 'required|string|min:8|max:32|confirmed',
        ]);

        if ($hasPassword && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password (auto-hashed by User model)
        $user->password = $request->new_password;
        $user->save();

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $user->forceFill([
                'current_session_id' => $request->session()->getId(),
            ])->save();
        }

        ActivityLog::logAction(
            $user->user_id,
            $hasPassword ? 'password_changed' : 'password_set',
            "Customer {$user->full_name} ({$user->email}) " . ($hasPassword ? 'changed' : 'set') . " their password."
        );

        return response()->json([
            'success' => true,
            'message' => $hasPassword ? 'Password changed successfully' : 'Password set successfully'
        ]);
    }
}
