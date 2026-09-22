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
            'email'   => [
                'required', 'email', 'max:150',
                'unique:users,email,' . $user->user_id . ',user_id',
            ],
            'address' => 'required|string|min:10|max:500',
        ], [
            'phone.required' => 'Phone number is required.',
            'phone.regex'    => 'Enter a valid PH number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
            'phone.unique'   => 'That phone number is already registered to another account.',
            'email.required' => 'Email address is required.',
            'email.email'    => 'Enter a valid email address.',
            'email.unique'   => 'That email address is already registered to another account.',
            'address.required' => 'Address is required.',
            'address.min'    => 'Address must be at least 10 characters.',
        ]);

        DB::transaction(function () use ($request, $user, $profile) {
            $profile->update($request->only(['phone', 'address']));

            if ($request->email !== $user->email) {
                $user->forceFill(['email' => $request->email])->save();
            }
        });

        ActivityLog::logAction(
            $user->user_id,
            'profile_updated',
            "Customer {$user->full_name} ({$user->email}) updated their profile."
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile->fresh('user')
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
