<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Says why the assistant is not using Groq, without anybody having to read
 * the key out loud.
 *
 * Four things go wrong and they look identical from the chat window: there is
 * no key, the key is wrong, the model named in .env no longer exists, or the
 * day's free allowance is used up. This asks Groq once and says which -- and
 * when it is the model, it lists the ones this key can actually reach, since
 * a hosted line-up changes without warning.
 */
class CheckAssistantKey extends Command
{
    protected $signature = 'assistant:check';

    protected $description = 'Check whether the shop assistant can reach Groq, and say what is wrong if it cannot';

    private const BASE = 'https://api.groq.com/openai/v1';

    public function handle(): int
    {
        $key = (string) config('chatbot.groq.key');
        $model = (string) config('chatbot.groq.model');

        $this->newLine();
        $this->line('  Model:  ' . ($model ?: 'not set'));

        if ($key === '') {
            $this->components->error('No key set. Add GROQ_API_KEY to your .env file, then restart the server.');
            $this->line('  Get one free at console.groq.com/keys -- no card, no billing account.');
            $this->line('  Until then the assistant answers from its own list of questions, which works but is limited.');

            return self::FAILURE;
        }

        // Described, never printed. Enough to tell one string from another
        // without putting the secret into a terminal scrollback.
        $this->line('  Key:    ' . strlen($key) . ' characters, starting "' . substr($key, 0, 4) . '..."');

        if (! str_starts_with($key, 'gsk_')) {
            $this->warn('  Groq keys normally begin "gsk_". Asking anyway.');
        }

        $this->newLine();
        $this->components->task('Asking Groq to say hello', function () use (&$outcome) {
            $outcome = $this->attempt();

            return $outcome['ok'];
        });

        $this->newLine();

        if ($outcome['ok']) {
            $this->components->info('The assistant is using Groq.');
            $this->line('  ' . $model . ' replied: ' . $outcome['detail']);

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
        $this->line('  Nothing is broken meanwhile. The assistant answers from the shop\'s own written answers');
        $this->line('  and its own database, which is also what runs when there is no internet.');
        $this->newLine();

        return self::FAILURE;
    }

    /** @return array{ok:bool,detail:string} */
    private function attempt(): array
    {
        try {
            $response = Http::timeout(20)
                ->withToken((string) config('chatbot.groq.key'))
                ->asJson()
                ->post(self::BASE . '/chat/completions', [
                    'model' => config('chatbot.groq.model'),
                    'messages' => [['role' => 'user', 'content' => 'Reply with the single word: ready']],
                    'max_completion_tokens' => 200,
                    'temperature' => 0,
                ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'detail' => 'Could not reach Groq at all. Check the internet connection. (' . $e->getMessage() . ')'];
        }

        if ($response->successful()) {
            $text = trim((string) data_get($response->json(), 'choices.0.message.content', ''));

            return ['ok' => true, 'detail' => $text !== '' ? $text : '(an empty answer, but the key works)'];
        }

        // Groq's own message says more than the status does, and carries no
        // secret of ours.
        $message = (string) data_get($response->json(), 'error.message', $response->body());

        return ['ok' => false, 'detail' => match ($response->status()) {
            401 => "The key was refused. Either it is mistyped or it has been revoked.\n"
                . '  Make a new one at console.groq.com/keys and replace GROQ_API_KEY in .env.',

            // The one worth being helpful about: Groq retires models on a few
            // weeks' notice, so the fix is a name from a list rather than
            // anything wrong at this end.
            404 => "No such model as \"" . config('chatbot.groq.model') . "\" on this key.\n"
                . "  Groq retires models fairly often. Set GROQ_MODEL in .env to one of these:\n"
                . $this->availableModels(),

            413, 422 => 'The request was rejected as too large or malformed: ' . $message,
            429 => "The free allowance is used up for now. The key works; Groq is rate limiting it.\n"
                . '  It resets on a rolling window, so this clears itself. Groq said: ' . $message,
            400 => "Refused as a bad request: " . $message . "\n"
                . '  If GROQ_REASONING_EFFORT is set in .env, try clearing it -- not every model accepts it.',
            500, 502, 503 => 'Groq is busy or down. Nothing wrong at this end. Try again shortly.',
            default => 'Refused with HTTP ' . $response->status() . '. ' . $message,
        }];
    }

    /**
     * The models this key can reach, asked for rather than remembered.
     *
     * Printed only when the configured one was refused, which is exactly when
     * a hard-coded list would be out of date too.
     */
    private function availableModels(): string
    {
        try {
            $response = Http::timeout(20)
                ->withToken((string) config('chatbot.groq.key'))
                ->get(self::BASE . '/models');

            if ($response->failed()) {
                return '    (could not list them: HTTP ' . $response->status() . ')';
            }

            $names = collect((array) data_get($response->json(), 'data', []))
                ->pluck('id')
                ->filter()
                // Whisper transcribes audio and Guard classifies it; neither
                // can hold a conversation, so neither belongs in this list.
                ->reject(fn ($id) => str_contains((string) $id, 'whisper') || str_contains((string) $id, 'guard'))
                ->sort()
                ->values();

            return $names->isEmpty()
                ? '    (the key can reach no chat models at all)'
                : $names->map(fn ($id) => '    - ' . $id)->implode("\n");
        } catch (Throwable $e) {
            return '    (could not list them: ' . $e->getMessage() . ')';
        }
    }
}
