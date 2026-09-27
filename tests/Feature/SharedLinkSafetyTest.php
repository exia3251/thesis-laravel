<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What a stranger sees when the laptop's port is shared, and what they must not.
 *
 * The system is demonstrated by tunnelling the development server to a public
 * address and putting that link in a survey form, so two different things are
 * true at once and it is worth keeping them apart.
 *
 * The seeded logins are shown on purpose. A respondent who has to register and
 * confirm an email before seeing anything is a respondent who closes the tab,
 * so the demo accounts are printed on the sign-in pages for anyone to copy.
 * That is a decision, and these tests hold it in place rather than letting a
 * later tidy-up quietly take it away.
 *
 * What is not a decision is the environment leaking. Laravel's error page
 * lists the database password and the assistant's API key, and no survey needs
 * either. That gate stays shut for anybody who did not come from this machine.
 */
class SharedLinkSafetyTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_PAGES = ['/admin/login', '/shop/login'];

    /** A tunnel address, which is what a respondent would be given. */
    private const SHARED = 'http://raney-demo.trycloudflare.com';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        // The suite runs as "testing", where the box is hidden whatever the
        // host. The behaviour under test is the one on a developer's machine.
        $this->app['env'] = 'local';
    }

    #[Test]
    public function a_visitor_following_the_link_is_given_a_login_to_copy(): void
    {
        $shop = $this->get(self::SHARED . '/shop/login')->assertOk()->getContent();

        $this->assertStringContainsString('john@example.com', $shop);
        $this->assertStringContainsString('customer123', $shop);
    }

    #[Test]
    public function the_staff_logins_are_offered_the_same_way(): void
    {
        $admin = $this->get(self::SHARED . '/admin/login')->assertOk()->getContent();

        foreach ([
            'admin@raney.test' => 'admin123',
            'inventory@raney.test' => 'inventory123',
            'accounting@raney.test' => 'accounting123',
        ] as $email => $password) {
            $this->assertStringContainsString($email, $admin);
            $this->assertStringContainsString($password, $admin);
        }
    }

    #[Test]
    public function the_logins_shown_are_ones_that_actually_work(): void
    {
        // A printed password that has drifted from the seeder is worse than
        // none: the respondent reads it off the page, it fails, they leave.
        // Checked against the credentials themselves rather than by posting
        // the form, which is driven by fetch and wants a CSRF token.
        foreach ([
            'john@example.com' => 'customer123',
            'admin@raney.test' => 'admin123',
            'inventory@raney.test' => 'inventory123',
            'accounting@raney.test' => 'accounting123',
        ] as $email => $password) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Auth::guard('web')->validate(['email' => $email, 'password' => $password]),
                "The page prints {$email} / {$password}, and that pair does not sign in."
            );
        }
    }

    #[Test]
    public function the_pages_themselves_still_work_for_a_visitor(): void
    {
        foreach (self::LOGIN_PAGES as $page) {
            $this->get(self::SHARED . $page)->assertOk()->assertSee('Sign in', false);
        }
    }

    #[Test]
    public function a_door_that_cannot_open_is_not_shown(): void
    {
        /*
         * Google Sign-In is switched off for the demonstration: Google will
         * not accept an ngrok address as an authorised domain, so the app
         * cannot be published, so only named test users could have used it.
         * A button that sends a respondent to a Google error page is worse
         * than no button, so neither the button nor the route is left open.
         */
        config(['services.google.enabled' => false]);

        foreach (self::LOGIN_PAGES as $page) {
            $body = $this->get(self::SHARED . $page)->assertOk()->getContent();

            $this->assertStringNotContainsString('/auth/google/redirect', $body, $page);
        }

        $this->get(self::SHARED . '/auth/google/redirect')
            ->assertRedirect('/shop/login');
    }

    #[Test]
    public function the_button_comes_back_when_it_is_switched_on(): void
    {
        // Switched off, not removed. It still works on localhost, where the
        // callback is an address Google is willing to accept.
        config([
            'services.google.enabled' => true,
            'services.google.client_id' => 'test-client-id.apps.googleusercontent.com',
            'services.google.client_secret' => 'test-secret',
        ]);

        $this->get('http://localhost/shop/login')
            ->assertOk()
            ->assertSee('/auth/google/redirect', false);
    }

    #[Test]
    public function a_real_deployment_drops_the_whole_box(): void
    {
        // The one gate left. Whatever is decided about a survey link, a
        // production environment never prints a password on a login page.
        $this->app['env'] = 'production';

        foreach (self::LOGIN_PAGES as $page) {
            $body = $this->get(self::SHARED . $page)->assertOk()->getContent();

            foreach (['admin123', 'inventory123', 'accounting123', 'customer123'] as $password) {
                $this->assertStringNotContainsString($password, $body, $page);
            }
        }
    }

    #[Test]
    public function a_request_from_elsewhere_is_not_treated_as_local(): void
    {
        /*
         * This is what keeps the error page quiet for a visitor. It is checked
         * on its own because the thing it protects -- APP_DEBUG, and with it
         * the database password and the assistant's key -- is decided once
         * when the app boots, which a request-level test cannot observe.
         */
        foreach (['raney-demo.trycloudflare.com', 'abc123.ngrok-free.app', '203.0.113.9', ''] as $host) {
            $this->assertFalse(AppServiceProvider::isLocalHost($host), "\"{$host}\" must not count as local.");
        }

        foreach (['localhost', '127.0.0.1', '::1'] as $host) {
            $this->assertTrue(AppServiceProvider::isLocalHost($host), "\"{$host}\" must count as local.");
        }
    }
}
