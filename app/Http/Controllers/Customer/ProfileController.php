<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
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
     * Update profile
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $profile = CustomerProfile::with('user')
            ->where('user_id', $user->user_id)
            ->first();

        $request->validate([
            'phone'   => [
                'required', 'string',
                'regex:/^(09\d{9}|\+639\d{9})$/',
                'unique:customer_profiles,phone,' . ($profile?->profile_id ?? 0) . ',profile_id',
            ],
            'email'   => [
                'nullable', 'email', 'max:100',
                'unique:customer_profiles,email,' . ($profile?->profile_id ?? 0) . ',profile_id',
            ],
            'address' => 'required|string|min:10|max:500',
        ], [
            'phone.required' => 'Phone number is required.',
            'phone.regex'    => 'Enter a valid PH number e.g. 09XXXXXXXXX or +639XXXXXXXXX.',
            'phone.unique'   => 'That phone number is already registered to another account.',
            'address.required' => 'Address is required.',
            'address.min'    => 'Address must be at least 10 characters.',
            'email.unique'   => 'That email address is already registered to another account.',
        ]);

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->update($request->only(['phone', 'email', 'address']));

        ActivityLog::logAction(
            $user->user_id,
            'profile_updated',
            "Customer {$profile->user->full_name} (@{$user->username}) updated their profile.",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $profile
        ]);
    }

    /**
     * Change password with Hash
     */
    public function changePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|max:32|confirmed',
        ]);

        $user = User::findOrFail($user->user_id);

        // Verify current password with Hash::check
        if (!Hash::check($request->current_password, $user->password)) {
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
            'password_changed',
            "Customer {$user->full_name} (@{$user->username}) changed their password.",
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
