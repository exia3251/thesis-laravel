<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Says why the assistant is not using Gemini, without anybody having to read
 * the key out loud.
 *
 * Three things go wrong and they look identical from the chat window: the key
 * is missing, the key is the wrong string entirely, or the key is right and
 * the model name is not. This asks the API once and says which.
 */
class CheckAssistantKey extends Command
{
    protected $signature = 'assistant:check';

    protected $description = 'Check whether the shop assistant can reach Gemini, and say what is wrong if it cannot';

    public function handle(): int
    {
        $key = (string) config('chatbot.gemini.key');
        $model = (string) config('chatbot.gemini.model');

        $this->newLine();
        $this->line('  Model:  ' . ($model ?: 'not set'));

        if ($key === '') {
            $this->components->error('No key set. Add GEMINI_API_KEY to your .env file, then restart the server.');
            $this->line('  The assistant is answering from its own list of questions, which is fine but limited.');

            return self::FAILURE;
        }

        // Described, never printed. Enough to tell one kind of string from
        // another without putting the secret on screen or in a scrollback.
        $this->line('  Key:    ' . strlen($key) . ' characters, starting "' . substr($key, 0, 4) . '..."');

        /*
         * Both shapes are real. Keys issued before May 2026 begin AIza; ones
         * issued since begin "AQ." and are the only kind AI Studio hands out
         * now. Neither is wrong, so neither is refused here -- the API is
         * asked, and what it says is reported.
         */
        if (! str_starts_with($key, 'AIza') && ! str_starts_with($key, 'AQ.')) {
            $this->warn('  That is neither of the two shapes Google issues (AIza... or AQ....). Asking anyway.');
        }

        $this->newLine();
        $this->components->task('Asking Gemini to say hello', function () use (&$outcome) {
            $outcome = $this->attempt();

            return $outcome['ok'];
        });

        $this->newLine();

        if ($outcome['ok']) {
            $this->components->info('The assistant is using Gemini.');
            $this->line('  It replied: ' . $outcome['detail']);

            return self::SUCCESS;
        }

        $this->components->error($outcome['detail']);

        return self::FAILURE;
    }

    /** @return array{ok:bool,detail:string} */
    private function attempt(): array
    {
        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
            config('chatbot.gemini.model')
        );

        try {
            $response = Http::timeout(15)
                ->withHeaders(['x-goog-api-key' => (string) config('chatbot.gemini.key')])
                ->asJson()
                ->post($url, [
                'contents' => [['role' => 'user', 'parts' => [['text' => 'Reply with the single word: ready']]]],
                'generationConfig' => ['maxOutputTokens' => 200, 'temperature' => 0],
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'detail' => 'Could not reach Google at all. Check the internet connection. (' . $e->getMessage() . ')'];
        }

        if ($response->successful()) {
            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

            return ['ok' => true, 'detail' => $text !== '' ? $text : '(an empty answer, but the key works)'];
        }

        // Google's own message says more than the status does, and carries no
        // secret of ours.
        $message = (string) data_get($response->json(), 'error.message', $response->body());

        return ['ok' => false, 'detail' => match ($response->status()) {
            400 => 'Refused as a bad request. Usually the model name: ' . $message,
            /*
             * Sending the key three different ways -- header, bearer token,
             * query string -- produced this same answer, so it is not about
             * how it travels. It is the credential being refused, and the
             * usual cause is the project rather than the key: a Cloud
             * project made by hand does not have the Generative Language API
             * switched on, while the one AI Studio makes for itself does.
             */
            401, 403 => "The key was refused, and not because of how it was sent.
"
                . "  The likeliest cause is the project it belongs to: a project you created yourself does not
"
                . "  have the Generative Language API enabled until somebody enables it.
"
                . "  Either switch it on for that project at console.cloud.google.com/apis/library/generativelanguage.googleapis.com,
"
                . "  or make a key on AI Studio's own \"Default Gemini Project\", which comes with it enabled.
"
                . '  Google said: ' . $message,
            404 => 'No such model as "' . config('chatbot.gemini.model') . '". Set GEMINI_MODEL in .env to one that exists.',
            429 => 'Out of quota for now. The key works; Google is rate limiting it.',
            500, 503 => 'Google is busy or down. Nothing wrong at this end. Try again shortly.',
            default => 'Refused with HTTP ' . $response->status() . '. ' . $message,
        }];
    }
}
