<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * A staff member's own account.
 *
 * Deliberately behind no permission gate beyond being staff at all. Every
 * role reaches it, because the alternative was what this system did before:
 * an inventory staff member who wanted a new password had to ask an
 * administrator to set one, which left the administrator knowing it.
 *
 * What a person may change about themselves stops at their name, photograph
 * and password. Role and active status stay in User Management, so nobody
 * can promote themselves.
 */
class AccountController extends Controller
{
    /** The longest side a stored avatar needs, given where it is displayed. */
    private const AVATAR_PIXELS = 512;

    public function index()
    {
        return view('admin.account');
    }

    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role_label' => $user->roleLabel(),
                'avatar_url' => $user->avatarUrl(),
                'initials' => $user->initials(),
                'avatar_tone' => $user->avatarTone(),
                'last_signed_in' => $this->lastSignIn($user),
                'avatar_pixels' => self::AVATAR_PIXELS,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'full_name' => [
                'required', 'string', 'min:5', 'max:60',
                'regex:/^[A-Za-z]{2,}(\s[A-Za-z]{2,})+$/',
            ],
            'email' => [
                'required', 'email', 'max:150',
                'unique:users,email,' . $user->user_id . ',user_id',
            ],
            'avatar' => [
                'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048',
                'dimensions:min_width=200,min_height=200',
            ],
        ], [
            'full_name.regex' => 'Give your full name, at least two words.',
            'email.unique' => 'Another account already uses that address.',
            'avatar.dimensions' => 'That photo is too small. Use one at least 200 by 200 pixels.',
            'avatar.max' => 'That photo is over 2 MB. The form normally shrinks it before sending, so try a different file.',
        ]);

        $user->forceFill([
            'full_name' => $request->full_name,
            'email' => $request->email,
        ])->save();

        if ($request->hasFile('avatar')) {
            $this->replaceAvatar($user, $request->file('avatar')->store('avatars', 'public'));
        }

        ActivityLog::logAction(
            $user->user_id,
            'own_account_updated',
            $user->full_name . ' updated their own account details'
        );

        return response()->json([
            'success' => true,
            'message' => 'Saved.',
            'data' => ['avatar_url' => $user->fresh()->avatarUrl()],
        ]);
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if (blank($user->avatar_path)) {
            return response()->json(['success' => false, 'message' => 'There is no photo to remove.'], 422);
        }

        $this->replaceAvatar($user, null);

        ActivityLog::logAction($user->user_id, 'own_account_updated', $user->full_name . ' removed their profile photo');

        return response()->json(['success' => true, 'message' => 'Photo removed.']);
    }

    public function password(Request $request)
    {
        $user = User::findOrFail($request->user()->user_id);

        // A staff account created through Google Sign-In will not have one
        // yet, so there is nothing for it to confirm the first time.
        $hasPassword = filled($user->password);

        $request->validate([
            'current_password' => [$hasPassword ? 'required' : 'nullable', 'string', 'max:255'],
            'new_password' => 'required|string|min:8|max:32|confirmed',
        ], [
            'new_password.confirmed' => 'The two new passwords do not match.',
        ]);

        if ($hasPassword && !Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['current_password' => ['That is not your current password.']],
            ], 422);
        }

        $user->password = $request->new_password;
        $user->save();

        // Changing a password should not sign the person out of the session
        // they changed it in, so the stored session id moves with them.
        if ($request->hasSession()) {
            $request->session()->regenerate();
            $user->forceFill(['current_session_id' => $request->session()->getId()])->save();
        }

        ActivityLog::logAction(
            $user->user_id,
            $hasPassword ? 'own_password_changed' : 'own_password_set',
            $user->full_name . ' ' . ($hasPassword ? 'changed' : 'set') . ' their own password'
        );

        return response()->json([
            'success' => true,
            'message' => $hasPassword ? 'Password changed.' : 'Password set.',
        ]);
    }

    /** Stores a new path and discards whatever it replaces. */
    private function replaceAvatar(User $user, ?string $path): void
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => $path])->save();
    }

    private function lastSignIn(User $user): ?string
    {
        // The current session's own login is the most recent row, so the one
        // before it is what "last signed in" usefully means.
        $previous = ActivityLog::where('user_id', $user->user_id)
            ->where('action', 'admin_login')
            ->orderByDesc('log_id')
            ->skip(1)
            ->take(1)
            ->value('created_at');

        return $previous ? \Illuminate\Support\Carbon::parse($previous)->diffForHumans() : null;
    }
}
