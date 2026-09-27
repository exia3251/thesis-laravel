<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The page a survey respondent is sent to.
 *
 * It exists because the link in the form otherwise lands somebody on a
 * sign-in page wanting an account they have not got. Everything it is for --
 * the two logins, the way through to each door, the list of things to try --
 * is checked here, because a broken landing page loses the respondent before
 * they have seen anything at all.
 */
class SurveyLandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // It is drawn only while the environment is local, like the demo
        // logins it prints.
        $this->app['env'] = 'local';
    }

    #[Test]
    public function it_hands_over_both_logins(): void
    {
        $page = $this->get('/survey')->assertOk();

        foreach ([
            'john@example.com', 'customer123',
            'admin@raney.test', 'admin123',
        ] as $credential) {
            $page->assertSee($credential, false);
        }
    }

    #[Test]
    public function it_points_at_both_doors(): void
    {
        $this->get('/survey')
            ->assertOk()
            ->assertSee('/shop/login', false)
            ->assertSee('/admin/login', false);
    }

    #[Test]
    public function it_warns_that_the_two_logins_are_not_interchangeable(): void
    {
        // The mistake that actually happened: a staff login typed into the
        // shop page, refused, and read as "these details are wrong".
        $this->get('/survey')
            ->assertOk()
            ->assertSee('take different accounts', false);
    }

    #[Test]
    public function it_lists_something_to_do_on_both_sides(): void
    {
        $page = $this->get('/survey')->assertOk();

        $page->assertSee('Worth trying as a customer', false);
        $page->assertSee('Worth trying as the business', false);
    }

    #[Test]
    public function the_logins_it_prints_are_ones_that_work(): void
    {
        // A landing page quoting a password that has drifted from the seeder
        // is worse than no landing page.
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        foreach ([
            'john@example.com' => 'customer123',
            'admin@raney.test' => 'admin123',
        ] as $email => $password) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Auth::guard('web')->validate(['email' => $email, 'password' => $password]),
                "The survey page prints {$email} / {$password}, and that pair does not sign in."
            );
        }
    }

    #[Test]
    public function the_form_link_appears_only_once_there_is_a_form(): void
    {
        config(['business.survey_form_url' => null]);
        $this->get('/survey')->assertOk()->assertDontSee('Open the survey form', false);

        config(['business.survey_form_url' => 'https://forms.gle/example']);
        $this->get('/survey')
            ->assertOk()
            ->assertSee('Open the survey form', false)
            ->assertSee('https://forms.gle/example', false);
    }

    #[Test]
    public function a_real_deployment_does_not_carry_it(): void
    {
        // Scaffolding for a survey, not part of the shop.
        $this->app['env'] = 'production';

        $this->get('/survey')->assertNotFound();
    }
}
