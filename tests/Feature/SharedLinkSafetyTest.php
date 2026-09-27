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
    public function the_wrong_door_says_which_one_is_right(): void
    {
        /*
         * The two sign-in pages take different accounts, and a staff login
         * typed into the shop one used to come back "Invalid credentials" --
         * the same answer as a wrong password. Somebody holding correct
         * details concludes the details are wrong, which is exactly what
         * happened the first time these pages were used in anger.
         */
        /*
         * CSRF is waived in tests, but only while the app says it is running
         * them -- and setUp puts it into "local" so the demo box renders.
         * These two post a form and do not care about the box, so they put
         * the environment back.
         */
        $this->app['env'] = 'testing';

        $this->postJson('/shop/login', ['email' => 'admin@raney.test', 'password' => 'admin123'])
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials. If this is a staff account, sign in at /admin/login instead.']);

        $this->postJson('/admin/login', ['email' => 'john@example.com', 'password' => 'customer123'])
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials. If this is a customer account, sign in at /shop/login instead.']);
    }

    #[Test]
    public function the_hint_gives_nothing_away_about_who_has_an_account(): void
    {
        $this->app['env'] = 'testing';

        // The same sentence for an address nobody has ever registered, so it
        // cannot be used to find out which accounts exist.
        $this->postJson('/shop/login', ['email' => 'nobody-at-all@example.org', 'password' => 'whatever'])
            ->assertStatus(401)
            ->assertJsonFragment(['message' => 'Invalid credentials. If this is a staff account, sign in at /admin/login instead.']);
    }

    #[Test]
    public function the_demo_logins_can_be_filled_in_with_one_tap(): void
    {
        $this->app['env'] = 'local';

        /*
         * The browser keeps a saved password per origin, and this system is
         * reached from several -- localhost, 127.0.0.1, the tunnel -- so
         * autofill puts an old one into a form whose correct password is
         * printed directly underneath it. Somebody reads the right password
         * off the page, watches it be refused, and concludes the system is
         * broken. A button that writes the pair in beats retyping it.
         */
        $this->get('http://localhost/shop/login')
            ->assertOk()
            ->assertSee("fillDemoAccount('john@example.com', 'customer123')", false);

        $admin = $this->get('http://localhost/admin/login')->assertOk();

        foreach ([
            'admin@raney.test' => 'admin123',
            'inventory@raney.test' => 'inventory123',
            'accounting@raney.test' => 'accounting123',
        ] as $email => $password) {
            $admin->assertSee("fillDemoAccount('{$email}', '{$password}')", false);
        }
    }

    #[Test]
    public function the_pages_still_carry_the_script_that_signs_people_in(): void
    {
        $this->app['env'] = 'local';

        // The fill helper was once added by cutting the file at @endsection,
        // which threw away the pushed script below it -- the whole login
        // handler. Both pages submitted as a plain GET after that, with the
        // password in the query string, and nothing said so.
        foreach (['/shop/login', '/admin/login'] as $page) {
            $this->get('http://localhost' . $page)
                ->assertOk()
                ->assertSee("getElementById('loginForm').addEventListener('submit'", false);
        }
    }

    #[Test]
    public function the_staff_page_points_back_at_the_shop(): void
    {
        $this->app['env'] = 'local';

        // One way only. A customer who lands on the staff page by accident
        // should be shown the way out; a customer on the shop page has no use
        // for a door they cannot open, so that link was taken away again.
        $this->get('http://localhost/admin/login')->assertOk()->assertSee('/shop/login', false);
        $this->get('http://localhost/shop/login')->assertOk()->assertDontSee('back office', false);
    }

    #[Test]
    public function a_signed_in_administrator_lands_on_the_dashboard(): void
    {
        /*
         * Both sign-in pages turn away visitors who are already signed in,
         * and used to send them to "/" -- the shop front. An administrator
         * who opened the staff login while still signed in was dropped on the
         * shop, which reads as the back office refusing them.
         */
        $this->app['env'] = 'testing';
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $admin = \App\Models\User::where('email', 'admin@raney.test')->firstOrFail();

        $this->actingAs($admin, 'staff')
            ->get('/admin/login')
            ->assertRedirect('/admin/dashboard');
    }

    #[Test]
    public function a_signed_in_customer_lands_on_the_shop(): void
    {
        // Its own test, because signing in as one guard in a test leaves that
        // guard resolved for the next assertion in the same one.
        $this->app['env'] = 'testing';
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $customer = \App\Models\User::where('email', 'john@example.com')->firstOrFail();

        $this->actingAs($customer, 'web')
            ->get('/shop/login')
            ->assertRedirect('/shop');
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
