<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The command whose only job is to say why the assistant is not using Gemini.
 *
 * Four refusals look identical from the chat window -- no key, a wrong key,
 * a project without the API switched on, and Google's own fault with the new
 * "AQ." keys -- and the last of those is the one somebody would otherwise
 * spend an evening trying to fix at this end. So each gets its own wording,
 * and each wording is checked here.
 */
class AssistantKeyCheckTest extends TestCase
{
    private const KEY = 'AQ.AbCdEf-this-is-not-a-real-key_0123456789';

    private function withKey(string $key = self::KEY): void
    {
        config(['chatbot.gemini.key' => $key, 'chatbot.gemini.model' => 'gemini-3.8-flash']);
    }

    /** What Google actually returns for one of the new keys on an affected account. */
    private function refusingTheKeyAsTheWrongKind(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => [
                'code' => 401,
                'status' => 'UNAUTHENTICATED',
                'message' => 'Request had invalid authentication credentials. Expected OAuth 2 access token, '
                    . 'login cookie or other valid authentication credential.',
            ],
        ], 401)]);
    }

    #[Test]
    public function it_says_what_to_set_when_there_is_no_key(): void
    {
        config(['chatbot.gemini.key' => null]);
        Http::fake();

        $this->artisan('assistant:check')
            ->expectsOutputToContain('GEMINI_API_KEY')
            ->assertFailed();

        Http::assertNothingSent();
    }

    #[Test]
    public function it_names_googles_own_fault_rather_than_blaming_this_end(): void
    {
        $this->withKey();
        $this->refusingTheKeyAsTheWrongKind();

        $this->artisan('assistant:check')
            ->expectsOutputToContain('wrong kind of credential')
            ->expectsOutputToContain('Default Gemini Project')
            ->assertFailed();
    }

    #[Test]
    public function it_says_the_shop_still_has_an_assistant_without_it(): void
    {
        $this->withKey();
        $this->refusingTheKeyAsTheWrongKind();

        // Whoever runs this is worried the chatbot is broken. It is not, and
        // that is the most useful sentence in the output.
        $this->artisan('assistant:check')
            ->expectsOutputToContain('Nothing is broken meanwhile')
            ->assertFailed();
    }

    #[Test]
    public function a_forbidden_project_is_told_to_switch_the_api_on(): void
    {
        $this->withKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['status' => 'PERMISSION_DENIED', 'message' => 'Generative Language API has not been used.'],
        ], 403)]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('generativelanguage.googleapis.com')
            ->assertFailed();
    }

    #[Test]
    public function a_missing_model_is_not_reported_as_a_key_problem(): void
    {
        $this->withKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['status' => 'NOT_FOUND', 'message' => 'models/gemini-3.8-flash is not found'],
        ], 404)]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('GEMINI_MODEL')
            ->assertFailed();
    }

    #[Test]
    public function it_reports_success_when_the_key_works(): void
    {
        $this->withKey();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => 'ready']]]]],
        ])]);

        $this->artisan('assistant:check')
            ->expectsOutputToContain('using Gemini')
            ->assertSuccessful();
    }

    #[Test]
    public function it_never_prints_the_key(): void
    {
        $this->withKey();
        $this->refusingTheKeyAsTheWrongKind();

        // It describes the key -- length, first four characters -- because
        // that is enough to tell one string from another, and a secret read
        // out into a terminal scrollback stops being one.
        $this->artisan('assistant:check')
            ->doesntExpectOutputToContain(self::KEY)
            ->doesntExpectOutputToContain('0123456789')
            ->assertFailed();
    }
}
