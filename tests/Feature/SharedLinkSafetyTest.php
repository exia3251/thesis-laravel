<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a stranger sees when the laptop's port is shared.
 *
 * The system is demonstrated by tunnelling the development server to a public
 * address and putting that link in a survey form. The environment is still
 * "local" the whole time, so anything gated on APP_ENV alone stays visible --
 * which is how the administrator's password came to be printed on a page
 * about to be handed to strangers.
 *
 * These run through both hosts and check the same pages twice. The seeded
 * logins are a convenience worth keeping on the machine they were seeded on,
 * and nowhere else.
 */
class SharedLinkSafetyTest extends TestCase
{
    use RefreshDatabase;

    /** Every seeded password, which is what must not travel. */
    private const SEEDED_PASSWORDS = ['admin123', 'inventory123', 'accounting123', 'customer123'];

    private const LOGIN_PAGES = ['/admin/login', '/shop/login'];

    /** A tunnel address, which is what a respondent would be given. */
    private const SHARED = 'http://raney-demo.trycloudflare.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    #[Test]
    public function no_seeded_password_is_printed_to_a_visitor_from_anywhere_else(): void
    {
        foreach (self::LOGIN_PAGES as $page) {
            $body = $this->get(self::SHARED . $page)->assertOk()->getContent();

            foreach (self::SEEDED_PASSWORDS as $password) {
                $this->assertStringNotContainsString(
                    $password,
                    $body,
                    "{$page} printed the seeded password \"{$password}\" to a visitor who did not come from this machine."
                );
            }
        }
    }

    #[Test]
    public function no_seeded_email_is_printed_either(): void
    {
        // The address alone is half of a guessable pair, and it also says
        // which accounts exist, which is worth nothing to a respondent.
        foreach (self::LOGIN_PAGES as $page) {
            $body = $this->get(self::SHARED . $page)->assertOk()->getContent();

            foreach (['admin@raney.test', 'inventory@raney.test', 'accounting@raney.test'] as $email) {
                $this->assertStringNotContainsString($email, $body, $page);
            }
        }
    }

    #[Test]
    public function the_seeded_logins_are_still_there_on_this_machine(): void
    {
        // The suite runs as "testing", where the box is hidden whatever the
        // host. This is about the other gate: on a developer's machine, where
        // the environment is local, the host is what decides.
        $this->app['env'] = 'local';

        // The point is not to delete the convenience. Somebody demonstrating
        // the system on their own screen should still be able to read the
        // password off the page rather than go looking for it.
        $this->assertStringContainsString(
            'admin123',
            $this->get('http://localhost/admin/login')->assertOk()->getContent()
        );

        $this->assertStringContainsString(
            'customer123',
            $this->get('http://localhost/shop/login')->assertOk()->getContent()
        );
    }

    #[Test]
    public function the_pages_themselves_still_work_for_a_visitor(): void
    {
        // Hiding the box must not take the sign-in form with it.
        foreach (self::LOGIN_PAGES as $page) {
            $this->get(self::SHARED . $page)
                ->assertOk()
                ->assertSee('Sign in', false);
        }
    }
}
