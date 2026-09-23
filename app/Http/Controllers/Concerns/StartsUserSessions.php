<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Opening a session for an account that has already proved who it is.
 *
 * Shared between the password form and Google Sign-In so the two cannot
 * drift: both regenerate the session, both record the new id against the
 * account for the single-session rule, and both leave the same trail in the
 * activity log. A second way in that forgot one of those would quietly
 * defeat the rule the first one enforces.
 */
trait StartsUserSessions
{
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
}
