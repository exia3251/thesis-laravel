<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The command whose only job is to say why the assistant is not using Groq.
 *
 * Four refusals look identical from the chat window -- no key, a wrong key, a
 * model that has been retired, and the day's free allowance spent -- and only
 * one of them is worth doing anything about. So each gets its own wording,
 * and each wording is checked here.
 */
class AssistantKeyCheckTest extends TestCase
{
    private const KEY = 'gsk_notARealKey_0123456789abcdef';

    private function withKey(string $key = self::KEY): void
    {
        config(['chatbot.groq.key' => $key, 'chatbot.groq.model' => 'openai/gpt-oss-120b']);
    }

    #[Test]
    public function it_says_what_to_set_when_there_is_no_key(): void
    {
        config(['chatbot.groq.key' => null]);
        Http::fake();

        $this->artisan('assistant:check')
            ->expectsOutputToContain('GROQ_API_KEY')
            ->expectsOutputToContain('console.groq.com/keys')
            ->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_reports_success_when_the_key_works(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response([
            'choices' => [['message' => ['content' => 'ready'], 'finish_reason' => 'stop']],
        ])]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('using Groq')
            ->assertSuccessful();
    }

    #[Test]
    public function a_rejected_key_is_reported_as_a_key_problem(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response([
            'error' => ['message' => 'Invalid API Key'],
        ], 401)]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('The key was refused')
            ->expectsOutputToContain('console.groq.com/keys')
            ->assertFailed();
    }

    #[Test]
    public function a_retired_model_is_answered_with_the_ones_that_exist(): void
    {
        $this->withKey();

        // Groq retires models on a few weeks' notice, so the useful reply
        // here is a name to put in .env rather than a diagnosis.
        Http::fake([
            'api.groq.com/openai/v1/chat/completions' => Http::response([
                'error' => ['message' => 'The model does not exist', 'code' => 'model_not_found'],
            ], 404),
            'api.groq.com/openai/v1/models' => Http::response([
                'data' => [
                    ['id' => 'llama-3.3-70b-versatile'],
                    ['id' => 'openai/gpt-oss-20b'],
                    ['id' => 'whisper-large-v3'],
                ],
            ]),
        ]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('GROQ_MODEL')
            ->expectsOutputToContain('llama-3.3-70b-versatile')
            ->expectsOutputToContain('openai/gpt-oss-20b')
            // Whisper transcribes audio; it cannot hold a conversation, so
            // offering it as a choice would only waste somebody's evening.
            ->doesntExpectOutputToContain('whisper-large-v3')
            ->assertFailed();
    }

    #[Test]
    public function a_spent_allowance_is_not_reported_as_a_broken_key(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response([
            'error' => ['message' => 'Rate limit reached for model'],
        ], 429)]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('The key works')
            ->assertFailed();
    }

    #[Test]
    public function a_bad_request_points_at_the_setting_that_usually_causes_it(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response([
            'error' => ['message' => 'unknown field reasoning_effort'],
        ], 400)]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('GROQ_REASONING_EFFORT')
            ->assertFailed();
    }

    #[Test]
    public function it_says_the_shop_still_has_an_assistant_without_it(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'nope']], 401)]);

        // Whoever runs this is worried the chatbot is broken. It is not, and
        // that is the most useful sentence in the output.
        $this->artisan('assistant:check')
            ->expectsOutputToContain('Nothing is broken meanwhile')
            ->assertFailed();
    }

    #[Test]
    public function it_never_prints_the_key(): void
    {
        $this->withKey();
        Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'nope']], 401)]);

        // It describes the key -- length, first four characters -- because
        // that is enough to tell one string from another, and a secret read
        // out into a terminal scrollback stops being one.
        $this->artisan('assistant:check')
            ->doesntExpectOutputToContain(self::KEY)
            ->doesntExpectOutputToContain('0123456789abcdef')
            ->assertFailed();
    }
}
