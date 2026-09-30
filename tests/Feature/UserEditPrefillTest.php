<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What the Edit User panel has to fill itself in from.
 *
 * The panel opened blank in the email field for every account. It read the
 * address from the account and then read it again from the customer profile --
 * which is where it used to live, before users.email became the one login
 * identifier and the copy on customer_profiles was dropped. The second read
 * found a column that no longer exists, so it wrote an empty string over the
 * address it had just put there.
 *
 * The fault was invisible until saving: the form posted an empty email, and
 * validation refused it for an address the administrator could read in the row
 * behind the panel.
 *
 * These tests pin the contract the panel now reads, and the dropped column
 * that made the old read wrong.
 */
class UserEditPrefillTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'email' => 'boss@raney.test',
            'password' => 'a-password',
            'full_name' => 'The Administrator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function listed(User $user): array
    {
        $rows = $this->actingAs($this->admin, 'staff')
            ->getJson('/admin-api/users')
            ->assertOk()
            ->json('data');

        $row = collect($rows)->firstWhere('user_id', $user->user_id);

        $this->assertNotNull($row, 'The account was not in the list at all.');

        return $row;
    }

    // ---- The column the old read reached for ---------------------------

    #[Test]
    public function the_customer_profile_no_longer_carries_an_email(): void
    {
        // The reason the second read could only ever find nothing. If this
        // ever comes back, the duplicate is the thing to question.
        $this->assertFalse(Schema::hasColumn('customer_profiles', 'email'));
        $this->assertTrue(Schema::hasColumn('users', 'email'));
    }

    #[Test]
    public function no_view_fills_a_form_from_the_dropped_column(): void
    {
        // Written as a sweep rather than against one page, because the same
        // stale read could sit in any of them and would fail the same silent
        // way -- a blank field nobody notices until a save is refused.
        foreach (glob(resource_path('views/**/*.blade.php')) as $view) {
            $this->assertStringNotContainsString(
                'customer_profile.email',
                file_get_contents($view),
                basename($view) . ' still reads the email off the customer profile.'
            );
        }
    }

    // ---- What the panel reads instead ----------------------------------

    #[Test]
    public function a_staff_account_arrives_with_its_email(): void
    {
        $staff = User::create([
            'email' => 'stock@raney.test',
            'password' => 'a-password',
            'full_name' => 'Stock Keeper',
            'role' => User::ROLE_INVENTORY_STAFF,
            'is_active' => true,
        ]);

        $row = $this->listed($staff);

        $this->assertSame('stock@raney.test', $row['email']);
        $this->assertSame('Stock Keeper', $row['full_name']);

        // No profile at all, which is exactly the case the old read blanked.
        $this->assertNull($row['customer_profile'] ?? null);
    }

    #[Test]
    public function a_customer_arrives_with_both_the_email_and_the_address(): void
    {
        $customer = User::create([
            'email' => 'buyer@example.com',
            'password' => 'a-password',
            'full_name' => 'A Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        CustomerProfile::create([
            'user_id' => $customer->user_id,
            'phone' => '09171234567',
            'house_street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Imus',
            'province' => 'Cavite',
            'postal_code' => '4103',
        ]);

        $row = $this->listed($customer);

        $this->assertSame('buyer@example.com', $row['email']);

        // Named in snake_case, which is the key the panel looks under.
        $this->assertArrayHasKey('customer_profile', $row);

        foreach ([
            'phone' => '09171234567',
            'house_street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Imus',
            'province' => 'Cavite',
            'postal_code' => '4103',
        ] as $field => $expected) {
            $this->assertSame($expected, $row['customer_profile'][$field], "The {$field} did not arrive.");
        }
    }

    #[Test]
    public function every_field_the_panel_fills_in_is_on_the_row_it_reads(): void
    {
        // The panel writes into these by id. Any one of them missing from the
        // payload is another field that opens blank and saves wrong.
        $row = $this->listed($this->admin);

        foreach (['user_id', 'full_name', 'email', 'role', 'is_active'] as $field) {
            $this->assertArrayHasKey($field, $row);
        }

        foreach (['avatar_url', 'initials', 'avatar_tone'] as $field) {
            $this->assertArrayHasKey($field, $row);
        }
    }

    #[Test]
    public function saving_an_account_back_unchanged_is_accepted(): void
    {
        // What the administrator was actually trying to do: open an account,
        // change one thing, save. With the email blanked it failed validation
        // every time; posting the row as it arrives has to work.
        $staff = User::create([
            'email' => 'money@raney.test',
            'password' => 'a-password',
            'full_name' => 'Ledger Keeper',
            'role' => User::ROLE_ACCOUNTING,
            'is_active' => true,
        ]);

        $row = $this->listed($staff);

        $this->actingAs($this->admin, 'staff')
            ->putJson("/admin-api/users/{$staff->user_id}", [
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'is_active' => $row['is_active'] ? '1' : '0',
            ])
            ->assertOk();

        $this->assertSame('money@raney.test', $staff->fresh()->email);
    }

    #[Test]
    public function saving_with_the_email_blanked_is_refused(): void
    {
        // The failure the old panel produced on every edit. Kept as a test so
        // the refusal stays a refusal -- silently accepting a blank login
        // address would be far worse than the bug it replaced.
        $staff = User::create([
            'email' => 'keep@raney.test',
            'password' => 'a-password',
            'full_name' => 'Kept Account',
            'role' => User::ROLE_ACCOUNTING,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'staff')
            ->putJson("/admin-api/users/{$staff->user_id}", [
                'full_name' => 'Kept Account',
                'email' => '',
                'role' => User::ROLE_ACCOUNTING,
                'is_active' => '1',
            ])
            ->assertStatus(422);

        $this->assertSame('keep@raney.test', $staff->fresh()->email);
    }
}
