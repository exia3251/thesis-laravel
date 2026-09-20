<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends mail without letting a mail failure undo real work.
 *
 * QUEUE_CONNECTION is sync, so mail goes out inside the request that triggered
 * it. An unreachable SMTP host would otherwise surface as a failed checkout or
 * a delivery that could not be confirmed, even though the order itself was
 * fine. Anything that goes wrong is logged and swallowed.
 */
class Notifier
{
    /**
     * @return bool whether the message actually went out.
     */
    public static function send(?string $to, Mailable $mailable, array $context = []): bool
    {
        if (blank($to)) {
            Log::warning('Skipped mail: no recipient address.', $context + [
                'mailable' => $mailable::class,
            ]);

            return false;
        }

        try {
            Mail::to($to)->send($mailable);

            return true;
        } catch (Throwable $e) {
            Log::error('Mail could not be sent.', $context + [
                'mailable' => $mailable::class,
                'recipient' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
