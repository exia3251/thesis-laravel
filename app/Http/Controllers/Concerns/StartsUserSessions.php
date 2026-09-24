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
    protected function startSession(
        Request $request,
        User $user,
        string $logAction,
        string $replacedNote,
        string $guard = 'web'
    ): void {
        $previousSessionId = $user->current_session_id;

        Auth::guard($guard)->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $sessionId = $request->session()->getId();

        /*
         * Every account signed in on this browser gets the new id, not only
         * the one that just logged in.
         *
         * Regenerating is what protects against session fixation, but it
         * also means the id changes under anybody already signed in on the
         * other guard. Their current_session_id would then point at a
         * session that no longer exists, and the single-session rule would
         * read that as "signed in somewhere else" and throw them out on
         * their very next click.
         */
        foreach (config('auth.session_guards') as $name) {
            $signedIn = Auth::guard($name)->user();

            if ($signedIn) {
                $signedIn->forceFill(['current_session_id' => $sessionId])->save();
            }
        }

        ActivityLog::logAction(
            $user->user_id,
            $logAction,
            "{$user->full_name} ({$user->email}) logged in as {$user->roleLabel()}"
        );

        if ($previousSessionId && $previousSessionId !== $sessionId) {
            ActivityLog::logAction($user->user_id, 'single_session_replaced', $replacedNote);
        }
    }
}
