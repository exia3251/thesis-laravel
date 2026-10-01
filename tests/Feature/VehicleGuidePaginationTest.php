<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleSpec;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The vehicle oil guide, which is checked a row at a time.
 *
 * Every row here is a general reference figure until somebody opens it,
 * compares it with the manufacturer's manual, and ticks it. That is the work
 * this screen exists for, and it is done sixty times in a row -- so the list
 * has to keep its place while it is being done, not start again from the top
 * after every save.
 */
class VehicleGuidePaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'email' => 'boss@raney.test',
            'password' => 'secret12345',
            'full_name' => 'Ana Reyes',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function spec(array $overrides = []): VehicleSpec
    {
        static $n = 0;
        $n++;

        return VehicleSpec::create(array_merge([
            'make' => 'Toyota',
            'model' => 'Model ' . str_pad((string) $n, 3, '0', STR_PAD_LEFT),
            'fuel' => 'gasoline',
            'viscosity' => '5W30',
            'is_verified' => false,
        ], $overrides));
    }

    private function fetch(User $admin, string $query = '')
    {
        return $this->actingAs($admin, 'staff')
            ->getJson('/admin-api/assistant/vehicles' . ($query ? '?' . $query : ''))
            ->assertOk();
    }

    #[Test]
    public function the_guide_is_paged_rather_than_sent_whole(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 70; $i++) {
            $this->spec();
        }

        $response = $this->fetch($admin, '');

        $this->assertCount(20, $response->json('data'));
        $this->assertSame(70, $response->json('meta.total'));
        $this->assertSame(4, $response->json('meta.last_page'));
    }

    /**
     * Ordering by make alone is not a total order: a make has many rows, and
     * rows that tie on every sorted column may come back in either order. On
     * a paged query that puts a row on two pages, or on none.
     */
    #[Test]
    public function no_vehicle_appears_on_two_pages_or_on_none(): void
    {
        $admin = $this->admin();

        // All the same make and model, so every sort column ties.
        for ($i = 0; $i < 50; $i++) {
            $this->spec(['make' => 'Toyota', 'model' => 'Vios']);
        }

        $seen = [];

        for ($page = 1; $page <= 3; $page++) {
            foreach ($this->fetch($admin, "page={$page}")->json('data') as $row) {
                $seen[] = $row['spec_id'];
            }
        }

        $this->assertCount(50, $seen, 'A page was short or long.');
        $this->assertCount(50, array_unique($seen), 'A vehicle was on two pages.');
    }



    /**
     * The heading says how large the guide is, so it counts the guide -- not
     * the search somebody happens to be running, and not the page they
     * happen to be reading.
     */
    #[Test]
    public function the_summary_counts_the_whole_guide(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 30; $i++) {
            $this->spec();
        }

        $this->spec(['make' => 'Mitsubishi', 'model' => 'Montero Sport']);

        $this->assertSame(31, $this->fetch($admin, 'page=1')->json('summary.total'));
        $this->assertSame(31, $this->fetch($admin, 'page=2')->json('summary.total'));

        // A search narrows the rows, not the size of the guide.
        $narrowed = $this->fetch($admin, 'search=montero');
        $this->assertSame(1, $narrowed->json('meta.total'));
        $this->assertSame(31, $narrowed->json('summary.total'));
    }

    /**
     * The screen no longer offers the tick, and a field nobody posts reads as
     * false. Writing it back on every save would clear the flag on the rows
     * that genuinely were checked against a manufacturer's manual, one save
     * at a time, with nothing on screen to show it happening.
     */
    #[Test]
    public function editing_a_row_does_not_wipe_the_record_of_it_having_been_checked(): void
    {
        $admin = $this->admin();

        $spec = $this->spec([
            'is_verified' => true,
            'source' => "2019 Vios owner's manual, page 312",
            'viscosity' => '5W30',
        ]);

        $this->actingAs($admin, 'staff')
            ->putJson("/admin-api/assistant/vehicles/{$spec->spec_id}", [
                'viscosity' => '0W20',
                'source' => "2019 Vios owner's manual, page 312",
            ])
            ->assertOk();

        $spec->refresh();

        $this->assertSame('0W20', $spec->viscosity, 'The edit did not take.');
        $this->assertTrue($spec->is_verified, 'The record of this row having been checked was wiped.');
    }

    #[Test]
    public function the_screen_no_longer_offers_the_verification_controls(): void
    {
        $view = file_get_contents(resource_path('views/admin/chatbot.blade.php'));

        foreach (['vehicleFilter', 'vehicleVerified', 'Not yet checked', 'Not checked',
                  'Checked against the manual', 'have not been checked against'] as $gone) {
            $this->assertStringNotContainsString($gone, $view, "\"{$gone}\" is still on the screen.");
        }

        // The row button opens the row for editing, and says so.
        $this->assertStringContainsString('>Edit</button>', $view);
        $this->assertStringNotContainsString('>Check</button>', $view);

        // The stocking note outlived the banner it used to sit in.
        $this->assertStringContainsString('id="unstockedNote"', $view);
    }

    #[Test]
    public function searching_still_narrows_the_whole_table_not_one_page(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 40; $i++) {
            $this->spec(['make' => 'Toyota']);
        }

        // Placed well past the first page if the search were ignored.
        $this->spec(['make' => 'Mitsubishi', 'model' => 'Montero Sport']);

        $response = $this->fetch($admin, 'search=montero');

        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Montero Sport', $response->json('data.0.model'));
    }

    #[Test]
    public function a_page_past_the_end_reports_the_real_total_so_the_page_can_step_back(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 25; $i++) {
            $this->spec();
        }

        // The state after checking the last row on the last page.
        $response = $this->fetch($admin, 'page=9');

        $this->assertSame([], $response->json('data'));
        $this->assertSame(25, $response->json('meta.total'));
        $this->assertSame(2, $response->json('meta.last_page'));
        $this->assertGreaterThan($response->json('meta.last_page'), $response->json('meta.current_page'));
    }

    #[Test]
    public function the_unstocked_grades_are_still_reported(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 25; $i++) {
            $this->spec(['viscosity' => '0W16']);
        }

        $grades = $this->fetch($admin, '')->json('summary.unstocked_grades');

        $this->assertNotEmpty($grades, 'A grade nobody stocks stopped being named.');
        $this->assertSame('0W16', $grades[0]['viscosity']);
        $this->assertSame(25, $grades[0]['vehicles']);
    }

    #[Test]
    public function nobody_can_ask_for_the_whole_table_in_one_request(): void
    {
        $admin = $this->admin();

        for ($i = 0; $i < 150; $i++) {
            $this->spec();
        }

        $response = $this->fetch($admin, 'per_page=5000');

        $this->assertLessThanOrEqual(100, count($response->json('data')));
        $this->assertSame(150, $response->json('meta.total'));
    }

    #[Test]
    public function the_page_keeps_its_place_after_a_row_is_checked(): void
    {
        $view = file_get_contents(resource_path('views/admin/chatbot.blade.php'));

        $this->assertStringContainsString('id="vehiclePagination"', $view);
        $this->assertStringContainsString("renderPagination('vehiclePagination'", $view);

        // The save handler returns to the page the row was on, not page one.
        $this->assertStringContainsString('await loadVehicles(vehiclePage);', $view);

        // A new search is a new list, so it does start at the top.
        $this->assertStringContainsString('debounce(() => loadVehicles(1))', $view);
    }

    #[Test]
    public function the_guide_is_no_longer_fetched_whole_in_the_controller(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/ChatbotAdminController.php'));

        $start = (int) strpos($source, 'public function vehicles');
        $vehicles = substr($source, $start, (int) (strpos($source, 'public function updateVehicle') - $start));

        $this->assertStringContainsString('->paginate(', $vehicles);
        $this->assertStringNotContainsString('->get();', $vehicles, 'The whole table is being fetched again.');

        // The tie-breaker is what keeps a row off two pages.
        $this->assertStringContainsString("orderBy('spec_id')", $vehicles);
    }
}
