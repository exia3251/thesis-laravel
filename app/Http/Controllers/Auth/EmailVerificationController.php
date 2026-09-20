<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailVerificationController extends Controller
{
    /**
     * Handle the link from the verification email.
     *
     * The signed middleware has already proved the URL came from us and has
     * not expired, so this does not additionally require a session: people
     * routinely open mail on a different device from the one they registered
     * on, and forcing a login first turns a one-click confirmation into a
     * dead end.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::find($id);

        if (!$user || !hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return view('auth.verify-result', [
                'ok' => false,
                'heading' => 'That link is not valid',
                'message' => 'It may have been copied incompletely, or the address has changed since it was sent.',
            ]);
        }

        if ($user->hasVerifiedEmail()) {
            return view('auth.verify-result', [
                'ok' => true,
                'heading' => 'Already confirmed',
                'message' => 'This email address was confirmed previously. You can sign in and start shopping.',
            ]);
        }

        $user->markEmailAsVerified();

        ActivityLog::logAction($user->user_id, 'email_verified', "{$user->full_name} ({$user->email}) confirmed their email address.", $request->ip());

        return view('auth.verify-result', [
            'ok' => true,
            'heading' => 'Email confirmed',
            'message' => 'Thank you. You can now sign in and place orders.',
        ]);
    }

    /** Send the link again, for a signed-in customer who never got it. */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['success' => false, 'message' => 'Your email address is already confirmed.'], 422);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Verification mail failed.', ['user_id' => $user->user_id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'We could not send the email just now. Please try again shortly.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Verification link sent to ' . $user->email . '.',
        ]);
    }
}
