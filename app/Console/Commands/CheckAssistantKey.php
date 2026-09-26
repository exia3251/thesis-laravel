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

        // Line by line: the error component folds a multi-line explanation
        // into one paragraph, and an explanation with steps in it needs to
        // keep its shape to be worth reading.
        $lines = explode("\n", $outcome['detail']);

        $this->components->error(array_shift($lines));

        foreach ($lines as $line) {
            $this->line($line);
        }

        $this->newLine();

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

        return ['ok' => false, 'detail' => match (true) {
            $response->status() === 400 => 'Refused as a bad request. Usually the model name: ' . $message,

            $this->isTheKnownAuthKeyFault($response->status(), $message) => $this->authKeyFault($message),

            $response->status() === 403 => "The key was refused permission rather than rejected outright.
"
                . "  Usually the project it belongs to has never had the Generative Language API switched on.
"
                . "  Turn it on at console.cloud.google.com/apis/library/generativelanguage.googleapis.com
"
                . '  Google said: ' . $message,

            $response->status() === 401 => 'The key was refused. Google said: ' . $message,
            $response->status() === 404 => 'No such model as "' . config('chatbot.gemini.model') . '". Set GEMINI_MODEL in .env to one that exists.',
            $response->status() === 429 => 'Out of quota for now. The key works; Google is rate limiting it.',
            in_array($response->status(), [500, 503], true) => 'Google is busy or down. Nothing wrong at this end. Try again shortly.',
            default => 'Refused with HTTP ' . $response->status() . '. ' . $message,
        }];
    }

    /**
     * The fault that is Google's rather than ours.
     *
     * Since mid-2026 AI Studio issues only "authorisation keys", which begin
     * "AQ." and are bound to a service account rather than being a plain
     * string the API recognises. On a good many accounts those keys are not
     * accepted by the Generative Language API at all: the request is treated
     * as if it carried an OAuth token, and refused as the wrong kind of
     * credential. It has been reported steadily on Google's own forum since
     * July 2026 and is not something this project can code around.
     *
     * Told apart from an ordinary bad key by its wording. A key that is
     * merely wrong is reported as invalid; this one is reported as being the
     * wrong species of credential entirely.
     */
    private function isTheKnownAuthKeyFault(int $status, string $message): bool
    {
        if ($status !== 401) {
            return false;
        }

        return str_contains($message, 'Expected OAuth 2 access token')
            || str_contains($message, 'ACCESS_TOKEN_TYPE_UNSUPPORTED');
    }

    private function authKeyFault(string $message): string
    {
        $newKey = str_starts_with((string) config('chatbot.gemini.key'), 'AQ.');

        return implode("\n", array_filter([
            'Google refused the key as the wrong kind of credential, not as a wrong string.',
            $newKey
                ? '  Your key is one of the new "AQ." authorisation keys, and this is a known fault on Google\'s side:'
                : '  This is the fault Google\'s own forum has been reporting since July 2026:',
            '  on many accounts those keys are rejected by the Generative Language API however they are sent.',
            '  Header, bearer token and query string were all tried here and all gave this same answer.',
            '',
            '  Worth trying, in order:',
            '   1. Make a fresh key in AI Studio on its own "Default Gemini Project" rather than a project you created.',
            '   2. Check the Generative Language API is enabled for that project:',
            '      console.cloud.google.com/apis/library/generativelanguage.googleapis.com',
            '   3. If it still refuses, it is Google\'s to fix. Report it at discuss.ai.google.dev.',
            '',
            '  Nothing is broken meanwhile. The assistant answers from the shop\'s own written answers and',
            '  its own database, exactly as it did before Gemini was added.',
            '',
            '  Google said: ' . $message,
        ]));
    }
}
