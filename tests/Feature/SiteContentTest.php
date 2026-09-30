<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\MakesImages;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The parts of the storefront the business can change for itself.
 *
 * The telephone number, the address, the email, the opening hours and the
 * GCash QR code were fixed in configuration. Changing one meant editing a file
 * on the server and clearing a cache, which is not something the people who
 * run this shop should have to ask for.
 *
 * The design rests on one rule, and most of what follows is about it: nothing
 * has to be filled in. A key that has never been set falls back to the
 * configured default, so a fresh install reads exactly as it did before any of
 * this existed, and an editor that is never opened breaks nothing.
 */
class SiteContentTest extends TestCase
{
    use MakesImages;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        SiteContent::forget();
        Storage::fake('public');

        $this->admin = User::create([
            'email' => 'boss@raney.test',
            'password' => 'a-password',
            'full_name' => 'The Administrator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function staff(string $role, string $email): User
    {
        return User::create([
            'email' => $email,
            'password' => 'a-password',
            'full_name' => 'Some Staffer',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    // ---- Falling back ---------------------------------------------------

    #[Test]
    public function an_untouched_install_reads_exactly_as_it_did_before(): void
    {
        $content = SiteContent::all();

        $this->assertSame(config('business.phone'), $content['phone']);
        $this->assertSame(config('business.address'), $content['address']);
        $this->assertSame(config('business.email'), $content['email']);
        $this->assertSame(config('business.name'), $content['name']);
    }

    #[Test]
    public function an_edited_value_replaces_the_configured_one(): void
    {
        SiteContent::set('phone', '0917 000 1234');

        $this->assertSame('0917 000 1234', SiteContent::get('phone'));
    }

    #[Test]
    public function an_emptied_box_hides_the_line_rather_than_falling_back(): void
    {
        /*
         * The distinction the whole store rests on. A row that has never been
         * written falls back; a row written as an empty string is somebody
         * saying "show nothing here", and restoring the default over it would
         * ignore what they asked for.
         */
        SiteContent::set('phone', '');

        $this->assertSame('', SiteContent::get('phone'));
        $this->assertNotSame(config('business.phone'), SiteContent::get('phone'));
    }

    #[Test]
    public function the_footer_shows_an_edited_number_and_stops_showing_the_old_one(): void
    {
        SiteContent::set('phone', '0917 000 1234');

        $page = $this->get('/shop')->assertOk()->getContent();

        $this->assertStringContainsString('0917 000 1234', $page);
        $this->assertStringNotContainsString(config('business.phone'), $page);
    }

    #[Test]
    public function an_emptied_number_disappears_from_the_footer(): void
    {
        SiteContent::set('phone', '');

        $this->get('/shop')->assertOk()->assertDontSee(config('business.phone'));
    }

    // ---- Who may edit ---------------------------------------------------

    #[Test]
    public function only_an_administrator_may_open_the_editor(): void
    {
        $this->actingAs($this->admin, 'staff')->get('/admin/cms')->assertOk();

        foreach ([User::ROLE_INVENTORY_STAFF => 'stock@raney.test',
                  User::ROLE_ACCOUNTING => 'books@raney.test'] as $role => $email) {
            $this->actingAs($this->staff($role, $email), 'staff')
                ->get('/admin/cms')
                ->assertRedirect();
        }
    }

    #[Test]
    public function a_customer_cannot_reach_the_editor_or_its_endpoints(): void
    {
        $customer = $this->staff(User::ROLE_CUSTOMER, 'buyer@example.com');

        $this->actingAs($customer, 'web')->getJson('/admin-api/site-content')->assertStatus(401);
        $this->actingAs($customer, 'web')
            ->putJson('/admin-api/site-content', ['phone' => '0900 000 0000'])
            ->assertStatus(401);
    }

    #[Test]
    public function a_signed_out_visitor_cannot_edit_anything(): void
    {
        $this->putJson('/admin-api/site-content', ['phone' => '0900 000 0000'])->assertStatus(401);

        $this->assertSame(config('business.phone'), SiteContent::get('phone'));
    }

    #[Test]
    public function staff_who_may_not_edit_content_are_refused_by_the_endpoint_too(): void
    {
        // Not only hidden from the menu: the endpoint refuses as well, because
        // a hidden link is not a permission.
        $this->actingAs($this->staff(User::ROLE_INVENTORY_STAFF, 'stock2@raney.test'), 'staff')
            ->putJson('/admin-api/site-content', ['phone' => '0900 000 0000'])
            ->assertStatus(403);
    }

    // ---- Saving ---------------------------------------------------------

    #[Test]
    public function an_administrator_saves_the_contact_details(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->putJson('/admin-api/site-content', [
                'name' => 'RANEY LUBRICANTS TRADING',
                'tagline' => 'Genuine lubricants, delivered across Cavite.',
                'address' => '1 New Street, Imus, Cavite',
                'phone' => '0917 000 1234',
                'email' => 'hello@raney.test',
                'hours' => 'Mon - Sun: 7AM - 7PM',
                'facebook' => 'https://www.facebook.com/raney',
            ])
            ->assertOk();

        $this->assertSame('1 New Street, Imus, Cavite', SiteContent::get('address'));
        $this->assertSame('hello@raney.test', SiteContent::get('email'));
        $this->assertSame('Genuine lubricants, delivered across Cavite.', SiteContent::get('tagline'));
    }

    #[Test]
    public function a_malformed_email_or_link_is_refused(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->putJson('/admin-api/site-content', ['email' => 'not-an-address'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->actingAs($this->admin, 'staff')
            ->putJson('/admin-api/site-content', ['facebook' => 'facebook.com/raney'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('facebook');

        $this->assertSame(config('business.email'), SiteContent::get('email'));
    }

    #[Test]
    public function saving_records_who_changed_it(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->putJson('/admin-api/site-content', ['phone' => '0917 000 1234'])
            ->assertOk();

        $this->assertDatabaseHas('activity_logs', ['action' => 'site_content_updated']);
    }

    #[Test]
    public function an_edit_shows_immediately_rather_than_after_the_cache_expires(): void
    {
        // The values are cached for the life of the application, so a save
        // that did not clear the cache would appear to do nothing at all.
        $this->assertSame(config('business.phone'), SiteContent::get('phone'));

        $this->actingAs($this->admin, 'staff')
            ->putJson('/admin-api/site-content', ['phone' => '0917 000 1234'])
            ->assertOk();

        $this->assertSame('0917 000 1234', SiteContent::get('phone'));
    }

    // ---- Pictures -------------------------------------------------------

    #[Test]
    public function an_administrator_uploads_a_logo_and_the_shop_shows_it(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
                'image' => $this->fakePng('logo.jpg', 512, 512),
            ])
            ->assertOk();

        $path = SiteContent::get('logo_path');

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->get('/shop')->assertOk()->assertSee(SiteContent::get('logo_url'), false);
    }

    #[Test]
    public function the_shop_keeps_its_wordmark_when_no_logo_is_set(): void
    {
        // The slot being empty is not a broken header.
        $page = $this->get('/shop')->assertOk()->getContent();

        $this->assertStringContainsString('RANEY', $page);
        $this->assertNull(SiteContent::get('logo_url'));
    }

    #[Test]
    public function a_banner_appears_behind_the_heading_when_one_is_set(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/banner', [
                'image' => $this->fakePng('banner.jpg', 1600, 600),
            ])
            ->assertOk();

        $this->get('/shop')->assertOk()->assertSee(SiteContent::get('banner_url'), false);
    }

    #[Test]
    public function replacing_a_picture_deletes_the_one_it_replaces(): void
    {
        // Otherwise every upload leaves its predecessor behind and the disk
        // fills one edit at a time.
        $this->actingAs($this->admin, 'staff')->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
            'image' => $this->fakePng('first.jpg', 512, 512),
        ])->assertOk();

        $first = SiteContent::get('logo_path');

        $this->actingAs($this->admin, 'staff')->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
            'image' => $this->fakePng('second.jpg', 512, 512),
        ])->assertOk();

        $second = SiteContent::get('logo_path');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    #[Test]
    public function removing_a_picture_returns_the_shop_to_its_built_in_design(): void
    {
        $this->actingAs($this->admin, 'staff')->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
            'image' => $this->fakePng('logo.jpg', 512, 512),
        ])->assertOk();

        $path = SiteContent::get('logo_path');

        $this->actingAs($this->admin, 'staff')
            ->deleteJson('/admin-api/site-content/images/logo')
            ->assertOk();

        $this->assertNull(SiteContent::get('logo_url'));
        Storage::disk('public')->assertMissing($path);
    }

    #[Test]
    public function a_file_that_is_not_a_picture_is_refused(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
                'image' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');

        $this->assertNull(SiteContent::get('logo_path'));
    }

    #[Test]
    public function a_scalable_vector_file_is_refused(): void
    {
        /*
         * An SVG is a document, not a picture. One served from our own address
         * would run whatever script it carried, in the browser of every
         * visitor to the shop.
         */
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/logo', [
                'image' => UploadedFile::fake()->create('logo.svg', 8, 'image/svg+xml'),
            ])
            ->assertStatus(422);

        $this->assertNull(SiteContent::get('logo_path'));
    }

    #[Test]
    public function a_picture_far_too_small_for_its_slot_is_refused(): void
    {
        // The browser crops and shrinks before sending, but a request can be
        // made without ever opening the page.
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/banner', [
                'image' => $this->fakePng('tiny.jpg', 120, 45),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    #[Test]
    public function a_picture_slot_that_does_not_exist_is_turned_away(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/../../etc/passwd', [
                'image' => $this->fakePng('x.jpg', 512, 512),
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function the_gcash_code_falls_back_to_the_one_that_ships_with_the_system(): void
    {
        // It was a file in public/images long before any of this existed, and
        // with nothing uploaded that file is still the right answer.
        $this->assertNotNull(SiteContent::get('gcash_qr_url'));
        $this->assertStringContainsString('gcash-qr.png', SiteContent::get('gcash_qr_url'));
    }

    #[Test]
    public function a_picture_address_never_carries_the_host_it_was_built_on(): void
    {
        /*
         * Found in a browser: the QR preview failed to load because the
         * address had been cached as an absolute one, built by whatever warmed
         * the cache. Warmed from the console it took APP_URL, which is not the
         * port this runs on. Warmed through the tunnel used for the survey it
         * would have taken the tunnel's address and then broken for anyone
         * working on the machine itself.
         *
         * Root-relative addresses are correct on every host at once, which is
         * the only answer that survives being reached three different ways.
         */
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/admin-api/site-content/images/logo', [
                'image' => $this->fakePng('logo.png', 512, 512),
            ])
            ->assertOk();

        foreach (['logo_url', 'gcash_qr_url'] as $key) {
            $url = SiteContent::get($key);

            $this->assertNotNull($url);
            $this->assertStringStartsWith('/', $url, "{$key} is not root-relative: {$url}");
            $this->assertStringNotContainsString('http://', $url);
            $this->assertStringNotContainsString('localhost', $url);
        }
    }

    #[Test]
    public function the_cache_holds_the_stored_rows_rather_than_anything_built_from_them(): void
    {
        // The guard behind the test above: caching a derived value is what
        // allowed a host to be baked in, so the cache holds only what was
        // stored and everything else is worked out per request.
        SiteContent::set('phone', '0917 000 1234');

        // Saving clears the cache, so it is empty until something reads.
        SiteContent::all();

        $cached = cache()->get('site.content');

        $this->assertIsArray($cached);
        $this->assertSame('0917 000 1234', $cached['phone'] ?? null);

        foreach (array_keys($cached) as $key) {
            $this->assertStringNotContainsString('_url', (string) $key,
                'An address is being cached, which bakes in the host that built it.');
        }
    }

    #[Test]
    public function the_bundled_gcash_code_is_shown_but_not_offered_for_removal(): void
    {
        // It is a fallback rather than something the business chose, and an
        // editor offering to remove it would be offering to remove nothing.
        $images = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/site-content')
            ->assertOk()
            ->json('data.images');

        $this->assertNotNull($images['gcash_qr']['url']);
        $this->assertFalse($images['gcash_qr']['uploaded']);

        $this->assertNull($images['logo']['url']);
        $this->assertFalse($images['logo']['uploaded']);
    }

    #[Test]
    public function an_uploaded_picture_is_marked_as_the_businesss_own(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/admin-api/site-content/images/gcash_qr', [
                'image' => $this->fakePng('qr.png', 800, 1000),
            ])
            ->assertOk();

        $images = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/site-content')
            ->assertOk()
            ->json('data.images');

        $this->assertTrue($images['gcash_qr']['uploaded']);
    }

    #[Test]
    public function an_uploaded_gcash_code_replaces_the_bundled_one(): void
    {
        $this->actingAs($this->admin, 'staff')
            ->withHeaders(['Accept' => 'application/json'])->post('/admin-api/site-content/images/gcash_qr', [
                'image' => $this->fakePng('qr.png', 800, 1000),
            ])
            ->assertOk();

        $this->assertStringNotContainsString('gcash-qr.png', SiteContent::get('gcash_qr_url'));
    }

    // ---- The editor -----------------------------------------------------

    #[Test]
    public function the_editor_offers_every_field_the_storefront_reads(): void
    {
        $data = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/site-content')
            ->assertOk()
            ->json('data');

        foreach (array_keys(SiteContent::TEXT_FIELDS) as $key) {
            $this->assertArrayHasKey($key, $data['fields'], "The editor has no box for {$key}.");
        }

        foreach (array_keys(SiteContent::IMAGE_FIELDS) as $key) {
            $this->assertArrayHasKey($key, $data['images'], "The editor has no slot for {$key}.");
        }
    }

    #[Test]
    public function the_editor_says_what_will_be_shown_if_a_box_is_left_empty(): void
    {
        $fields = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/site-content')
            ->assertOk()
            ->json('data.fields');

        $this->assertSame(config('business.phone'), $fields['phone']['fallback']);
        $this->assertFalse($fields['phone']['customised']);

        SiteSetting::create(['key' => 'phone', 'value' => '0917 000 1234']);
        SiteContent::forget();

        $fields = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/site-content')
            ->assertOk()
            ->json('data.fields');

        $this->assertTrue($fields['phone']['customised']);
    }

    #[Test]
    public function every_picture_slot_declares_the_shape_it_needs(): void
    {
        // The cropper is driven by these, so a slot without them would open a
        // dialog that crops to nothing in particular.
        foreach (SiteContent::IMAGE_FIELDS as $field => $shape) {
            foreach (['label', 'ratio', 'width', 'height', 'note'] as $key) {
                $this->assertArrayHasKey($key, $shape, "The {$field} slot has no {$key}.");
            }

            $this->assertGreaterThan(0, $shape['ratio']);
            $this->assertEqualsWithDelta($shape['ratio'], $shape['width'] / $shape['height'], 0.01);
        }
    }

    #[Test]
    public function the_cropper_is_one_partial_rather_than_a_copy_per_page(): void
    {
        /*
         * It was written for the staff photograph and lived inside that page.
         * The content editor needs the same thing, and a second copy of two
         * hundred lines of positioning arithmetic is a second copy to keep in
         * step -- which, on the evidence of this codebase, means two copies
         * that drift.
         */
        $this->assertFileExists(resource_path('views/partials/image-cropper.blade.php'));

        foreach (['admin/account.blade.php', 'admin/cms.blade.php'] as $view) {
            $source = file_get_contents(resource_path('views/' . $view));

            $this->assertStringContainsString("@include('partials.image-cropper')", $source);
            $this->assertStringNotContainsString('function applyCrop', $source,
                "{$view} carries its own copy of the cropper.");
        }
    }
}
