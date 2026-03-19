<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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
        $request->validate([
            'phone' => 'required|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'required'
        ]);

        $profile = CustomerProfile::where('user_id', auth()->id())->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found'
            ], 404);
        }

        $profile->update($request->only(['phone', 'email', 'address']));

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
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // requires new_password_confirmation
        ]);

        $user = User::find(auth()->id());

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

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }
}
