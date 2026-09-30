<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\PsgcLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Three address boxes that narrow each other, from the PSA's own lists.
 *
 * Province, city or municipality and barangay were free text. The same address
 * arrived spelled six ways -- "Imus", "imus city", "Imus, Cavite" -- nothing
 * downstream could group by area, and a barangay could be saved under a city
 * it does not sit in, which a courier only finds out at the door.
 *
 * The lists narrow in the browser. These tests are about the half that has to
 * hold when the browser is not involved: what the endpoints hand over, and
 * what the server refuses to save.
 */
class AddressLookupTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $codes = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // A slice of the real list rather than the whole 43,764 rows: two
        // provinces, three cities, four barangays, chosen to cover the cases
        // that actually bite -- a city the PSA writes as "City of Imus", a
        // barangay name that exists in two different cities, and Metro Manila,
        // which is a region standing in for a province.
        $this->place('042100000', 'Cavite', PsgcLocation::PROVINCE);
        $this->place('130000000', 'Metro Manila', PsgcLocation::PROVINCE);

        $this->place('042109000', 'City of Imus', PsgcLocation::CITY, '042100000');
        $this->place('042105000', 'City of Bacoor', PsgcLocation::CITY, '042100000');
        $this->place('137404000', 'Quezon City', PsgcLocation::CITY, '130000000');

        $this->place('042109003', 'Anabu I-A', PsgcLocation::BARANGAY, '042109000');
        $this->place('042109045', 'Poblacion I-A (Pob.)', PsgcLocation::BARANGAY, '042109000');
        $this->place('042105001', 'Alima', PsgcLocation::BARANGAY, '042105000');
        $this->place('137404011', 'Bagumbayan', PsgcLocation::BARANGAY, '137404000');
    }

    private function place(string $code, string $name, string $level, ?string $parent = null): void
    {
        PsgcLocation::create(compact('code', 'name', 'level') + ['parent_code' => $parent]);

        $this->codes[$name] = $code;
    }

    // ---- The lists -----------------------------------------------------

    #[Test]
    public function the_province_list_is_open_to_a_visitor_who_is_not_signed_in(): void
    {
        // The first form that needs these is registration, which by definition
        // has nobody signed in.
        $this->getJson('/shop-api/places/provinces')
            ->assertOk()
            ->assertJsonFragment(['code' => '042100000', 'name' => 'Cavite']);
    }

    #[Test]
    public function a_province_hands_over_its_cities_and_nobody_elses(): void
    {
        $names = collect($this->getJson('/shop-api/places/042100000')->assertOk()->json('data'))
            ->pluck('name');

        $this->assertEqualsCanonicalizing(['City of Bacoor', 'City of Imus'], $names->all());
        $this->assertNotContains('Quezon City', $names);
    }

    #[Test]
    public function a_city_hands_over_its_barangays_and_nobody_elses(): void
    {
        $names = collect($this->getJson('/shop-api/places/042109000')->assertOk()->json('data'))
            ->pluck('name');

        $this->assertEqualsCanonicalizing(['Anabu I-A', 'Poblacion I-A (Pob.)'], $names->all());
        $this->assertNotContains('Alima', $names);
    }

    #[Test]
    public function the_lists_come_back_in_alphabetical_order(): void
    {
        // A list of 97 barangays is scrolled as often as it is searched.
        $names = collect($this->getJson('/shop-api/places/042100000')->json('data'))->pluck('name')->all();

        $sorted = $names;
        sort($sorted);

        $this->assertSame($sorted, $names);
    }

    #[Test]
    public function a_code_that_is_not_a_code_is_turned_away(): void
    {
        $this->getJson('/shop-api/places/not-a-code')->assertStatus(422);
        $this->getJson('/shop-api/places/12345')->assertStatus(422);
    }

    #[Test]
    public function a_code_that_exists_but_holds_nothing_comes_back_empty(): void
    {
        // A barangay has nothing inside it. An empty list is the right answer,
        // not an error.
        $this->getJson('/shop-api/places/042109003')->assertOk()->assertJsonPath('data', []);
    }

    // ---- Turning saved names back into codes ---------------------------

    #[Test]
    public function a_name_written_the_way_a_person_writes_it_still_resolves(): void
    {
        // The PSA writes "City of Imus". Nobody else does.
        foreach (['Imus', 'imus', 'Imus City', 'City of Imus', 'IMUS CITY'] as $written) {
            $found = PsgcLocation::findNamed($written, PsgcLocation::CITY, '042100000');

            $this->assertSame('042109000', $found?->code, "\"{$written}\" did not resolve.");
        }
    }

    #[Test]
    public function a_barangay_is_only_found_inside_the_city_it_belongs_to(): void
    {
        // The whole point. "Alima" is in Bacoor, so asking for it in Imus has
        // to come back with nothing rather than with Bacoor's row.
        $this->assertNotNull(PsgcLocation::findNamed('Alima', PsgcLocation::BARANGAY, '042105000'));
        $this->assertNull(PsgcLocation::findNamed('Alima', PsgcLocation::BARANGAY, '042109000'));
    }

    #[Test]
    public function the_resolve_endpoint_returns_the_whole_chain(): void
    {
        $this->getJson('/shop-api/places/resolve?province=Cavite&city=Imus&barangay=Anabu I-A')
            ->assertOk()
            ->assertJsonPath('data.province.code', '042100000')
            ->assertJsonPath('data.city.code', '042109000')
            ->assertJsonPath('data.city.name', 'City of Imus')
            ->assertJsonPath('data.barangay.code', '042109003');
    }

    #[Test]
    public function a_name_that_matches_nothing_resolves_to_null_rather_than_failing(): void
    {
        // An address saved before these lists existed must stay visible to
        // whoever has to correct it.
        $this->getJson('/shop-api/places/resolve?province=Atlantis&city=Nowhere&barangay=Nothing')
            ->assertOk()
            ->assertJsonPath('data.province', null)
            ->assertJsonPath('data.city', null);
    }

    #[Test]
    public function a_city_under_the_wrong_province_does_not_resolve(): void
    {
        $this->getJson('/shop-api/places/resolve?province=Cavite&city=Quezon City')
            ->assertOk()
            ->assertJsonPath('data.province.code', '042100000')
            ->assertJsonPath('data.city', null);
    }

    // ---- What the server will and will not save ------------------------

    private function registration(array $address): array
    {
        return array_merge([
            'email' => 'new-customer@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
            'full_name' => 'Juan Dela Cruz',
            'phone' => '09171234567',
            'house_street' => '123 Rizal Street',
        ], $address);
    }

    #[Test]
    public function a_registration_naming_three_real_places_is_accepted(): void
    {
        $this->postJson('/shop/register', $this->registration([
            'province' => 'Cavite',
            'city' => 'City of Imus',
            'barangay' => 'Anabu I-A',
            'province_code' => '042100000',
            'city_code' => '042109000',
            'barangay_code' => '042109003',
        ]))->assertOk();

        $profile = CustomerProfile::whereHas('user', fn ($q) => $q->where('email', 'new-customer@example.com'))->first();

        $this->assertSame('Cavite', $profile->province);
        $this->assertSame('City of Imus', $profile->city);
        $this->assertSame('Anabu I-A', $profile->barangay);
    }

    #[Test]
    public function a_barangay_in_the_wrong_city_is_refused(): void
    {
        // Alima is in Bacoor. Posted under Imus, it has to be turned away --
        // this is the check the browser's narrowing cannot be trusted for.
        $this->postJson('/shop/register', $this->registration([
            'province' => 'Cavite',
            'city' => 'City of Imus',
            'barangay' => 'Alima',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('barangay');

        $this->assertDatabaseMissing('users', ['email' => 'new-customer@example.com']);
    }

    #[Test]
    public function a_city_in_the_wrong_province_is_refused(): void
    {
        $this->postJson('/shop/register', $this->registration([
            'province' => 'Cavite',
            'city' => 'Quezon City',
            'barangay' => 'Bagumbayan',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('city');
    }

    #[Test]
    public function a_province_nobody_has_heard_of_is_refused(): void
    {
        $this->postJson('/shop/register', $this->registration([
            'province' => 'Wakanda',
            'city' => 'City of Imus',
            'barangay' => 'Anabu I-A',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('province');
    }

    #[Test]
    public function only_the_box_that_is_wrong_is_complained_about(): void
    {
        /*
         * A bad province makes the city and barangay unresolvable too, but
         * saying so would point the reader at three boxes when one is wrong.
         * The rule stays quiet about a box whose parent is already failing.
         */
        $errors = $this->postJson('/shop/register', $this->registration([
            'province' => 'Wakanda',
            'city' => 'City of Imus',
            'barangay' => 'Anabu I-A',
        ]))->assertStatus(422)->json('errors');

        $this->assertArrayHasKey('province', $errors);
        $this->assertArrayNotHasKey('city', $errors);
        $this->assertArrayNotHasKey('barangay', $errors);
    }

    #[Test]
    public function a_posted_code_that_does_not_match_its_name_falls_back_to_the_name(): void
    {
        // The code is a shortcut, not an authority. A stale or mismatched one
        // must not be able to save an address the names do not support.
        $this->postJson('/shop/register', $this->registration([
            'province' => 'Cavite',
            'city' => 'City of Imus',
            'barangay' => 'Alima',
            'barangay_code' => '042109003', // Anabu I-A's code, on Alima's name
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('barangay');
    }

    #[Test]
    public function an_address_saved_before_the_lists_existed_still_validates(): void
    {
        /*
         * The reason matching forgives spelling. A customer whose address was
         * typed as "Imus" must be able to change their phone number without
         * the address they never touched refusing to save.
         */
        $customer = User::create([
            'email' => 'old-hand@example.com',
            'password' => 'a-password',
            'full_name' => 'Old Hand',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'user_id' => $customer->user_id,
            'phone' => '09170000000',
            'house_street' => '9 Old Street',
            'barangay' => 'Anabu I-A',
            'city' => 'Imus',        // not how the PSA writes it
            'province' => 'Cavite',
            'postal_code' => '4103',
        ]);

        $this->actingAs($customer, 'web')
            ->putJson('/shop-api/profile', [
                'full_name' => 'Old Hand',
                'email' => 'old-hand@example.com',
                'phone' => '09171111111',
                'house_street' => '9 Old Street',
                'barangay' => 'Anabu I-A',
                'city' => 'Imus',
                'province' => 'Cavite',
                'postal_code' => '4103',
            ])
            ->assertOk();

        $this->assertSame('09171111111', $customer->fresh()->customerProfile->phone);
    }

    #[Test]
    public function a_staff_account_may_still_be_saved_with_no_address_at_all(): void
    {
        // The back office shares these boxes, and nobody delivers to an
        // accountant.
        $admin = User::create([
            'email' => 'boss@raney.test',
            'password' => 'a-password',
            'full_name' => 'The Administrator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/users', [
                'email' => 'newstaff@raney.test',
                'password' => 'Str0ng-Passw0rd!',
                'full_name' => 'New Staffer',
                'role' => User::ROLE_ACCOUNTING,
                'is_active' => '1',
                'province' => '',
                'city' => '',
                'barangay' => '',
            ])
            ->assertOk();

        $this->assertDatabaseHas('users', ['email' => 'newstaff@raney.test']);
    }

    #[Test]
    public function the_back_office_is_held_to_the_same_rule_as_the_shop(): void
    {
        $admin = User::create([
            'email' => 'boss2@raney.test',
            'password' => 'a-password',
            'full_name' => 'The Administrator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'staff')
            ->postJson('/admin-api/users', [
                'email' => 'someone@example.com',
                'password' => 'Str0ng-Passw0rd!',
                'full_name' => 'Some Customer',
                'role' => User::ROLE_CUSTOMER,
                'is_active' => '1',
                'province' => 'Cavite',
                'city' => 'City of Imus',
                'barangay' => 'Alima',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('barangay');
    }

    // ---- An install that never seeded the list -------------------------

    #[Test]
    public function an_install_with_no_place_list_still_lets_somebody_register(): void
    {
        /*
         * Judged against an empty table every address in the country is
         * wrong. Failing closed would mean a fresh clone where somebody ran
         * `migrate` without `--seed` greets its first visitor with a sign-up
         * form that refuses every address and blames them for it.
         *
         * So a missing list degrades to the free text these fields were
         * before. This test exists to say that is deliberate, and to stop
         * anyone reading the pass as proof the checking works -- it is the
         * opposite.
         */
        PsgcLocation::query()->delete();

        $this->postJson('/shop/register', $this->registration([
            'province' => 'Somewhere',
            'city' => 'Anywhere',
            'barangay' => 'Nowhere',
        ]))->assertOk();
    }

    #[Test]
    public function the_place_list_is_part_of_a_standard_seed(): void
    {
        // Which is what keeps the fail-open above from being how it normally
        // runs. If this is ever dropped, every address stops being checked and
        // nothing else in the suite would notice.
        $this->assertStringContainsString(
            'PsgcLocationSeeder::class',
            file_get_contents(database_path('seeders/DatabaseSeeder.php')),
            'The standard seed no longer loads the place list, so addresses will not be checked.'
        );
    }

    #[Test]
    public function the_place_list_ships_with_the_repository(): void
    {
        /*
         * Committed rather than downloaded when seeding, so the system builds
         * with no internet and gives the same result on every machine -- which
         * matters when the panel has to be able to rebuild it from the repo.
         */
        $path = database_path('data/psgc.csv');

        $this->assertFileExists($path, 'The place-name list is missing, so seeding will fail.');

        $handle = fopen($path, 'r');
        $this->assertSame(['code', 'name', 'level', 'parent_code'], fgetcsv($handle));

        $levels = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 3) {
                $levels[$row[2]] = ($levels[$row[2]] ?? 0) + 1;
            }
        }

        fclose($handle);

        // Sanity, not exactness: the PSA republishes, and a rebuilt file with
        // a few more barangays in it should not fail the suite. A file that
        // lost a whole level should.
        $this->assertGreaterThan(80, $levels['province'] ?? 0);
        $this->assertGreaterThan(1500, $levels['city'] ?? 0);
        $this->assertGreaterThan(40000, $levels['barangay'] ?? 0);
    }

    // ---- The forms carry the lists -------------------------------------

    #[Test]
    public function the_registration_form_asks_with_the_three_lists(): void
    {
        $page = $this->get('/shop/register')->assertOk()->getContent();

        foreach (['reg_province', 'reg_city', 'reg_barangay'] as $field) {
            $this->assertStringContainsString("id=\"{$field}\"", $page);
            $this->assertStringContainsString("id=\"{$field}_search\"", $page);
        }

        // The street stays free text -- no list of those exists.
        $this->assertStringContainsString('id="house_street"', $page);
    }

    #[Test]
    public function the_profile_form_opens_already_showing_the_saved_address(): void
    {
        /*
         * Rendered with the page rather than fetched after it, so the boxes
         * arrive holding Cavite, Imus and the barangay -- codes and all, ready
         * to narrow -- instead of three empty boxes that fill a moment later.
         */
        $customer = User::create([
            'email' => 'shopper@example.com',
            'password' => 'a-password',
            'full_name' => 'A Shopper',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'user_id' => $customer->user_id,
            'phone' => '09172222222',
            'house_street' => '5 Mabini Street',
            'barangay' => 'Anabu I-A',
            'city' => 'Imus',
            'province' => 'Cavite',
        ]);

        $page = $this->actingAs($customer, 'web')->get('/profile')->assertOk()->getContent();

        $this->assertStringContainsString('value="042100000"', $page, 'The province code was not pre-filled.');
        $this->assertStringContainsString('value="042109000"', $page, 'The city code was not pre-filled.');
        $this->assertStringContainsString('value="042109003"', $page, 'The barangay code was not pre-filled.');

        // Shown under the list's own spelling, not the one that was saved.
        $this->assertStringContainsString('City of Imus', $page);
    }
}
