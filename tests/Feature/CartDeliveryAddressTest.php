<?php

namespace Tests\Feature;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The address the order is going to, shown where the order is placed.
 *
 * The cart's delivery panel read `profile.address`. That field stopped
 * existing when the single address line was split into house, barangay, city,
 * province and postal code, so the read found nothing and the panel told
 * everybody to go and complete their profile -- including customers whose
 * address was complete, saved, and printed on their last receipt. The one
 * thing that panel exists to confirm was the one thing it never said.
 *
 * It is the same fault as the blank email in Edit User, in a different file:
 * a page still asking for a column a migration took away. Neither failed
 * loudly. Both just showed nothing, which reads as "you have nothing saved"
 * rather than as a bug.
 */
class CartDeliveryAddressTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::create([
            'email' => 'cart-address@example.com',
            'password' => 'a-password',
            'full_name' => 'A Buyer',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);
    }

    private function profile(array $fields = []): CustomerProfile
    {
        return CustomerProfile::create(array_merge([
            'user_id' => $this->customer->user_id,
            'phone' => '09171234567',
            'house_street' => '123 Rizal Street',
            'barangay' => 'Poblacion',
            'city' => 'Imus',
            'province' => 'Cavite',
            'postal_code' => '4103',
        ], $fields));
    }

    private function payload(): array
    {
        return $this->actingAs($this->customer, 'web')
            ->getJson('/shop-api/profile')
            ->assertOk()
            ->json('data');
    }

    // ---- The field the panel reads -------------------------------------

    #[Test]
    public function the_profile_hands_over_a_composed_address(): void
    {
        $this->profile();

        $data = $this->payload();

        $this->assertSame(
            '123 Rizal Street, Brgy. Poblacion, Imus, Cavite, 4103',
            $data['full_address']
        );
    }

    #[Test]
    public function the_field_the_panel_used_to_read_is_gone(): void
    {
        // Named so the reason the panel was silent is on the record, rather
        // than looking like a styling choice to whoever reads this next.
        $this->profile();

        $this->assertArrayNotHasKey('address', $this->payload());
    }

    #[Test]
    public function a_complete_profile_says_so(): void
    {
        $this->profile();

        $data = $this->payload();

        $this->assertTrue($data['is_complete']);
        $this->assertNotSame('', $data['full_address']);
    }

    #[Test]
    public function an_address_with_no_phone_is_not_complete(): void
    {
        // Deliverable needs somebody the courier can ring, so the panel has to
        // keep asking even though the address itself reads fine.
        $this->profile(['phone' => null]);

        $data = $this->payload();

        $this->assertFalse($data['is_complete']);
        $this->assertStringContainsString('123 Rizal Street', $data['full_address']);
    }

    #[Test]
    public function a_profile_with_nothing_in_it_has_an_empty_address(): void
    {
        $this->profile([
            'phone' => null,
            'house_street' => null,
            'barangay' => null,
            'city' => null,
            'province' => null,
            'postal_code' => null,
        ]);

        $data = $this->payload();

        $this->assertSame('', $data['full_address']);
        $this->assertFalse($data['is_complete']);
    }

    #[Test]
    public function a_postal_code_is_not_required_to_be_deliverable(): void
    {
        // Couriers here manage without one, and demanding it would block
        // checkout for people who simply do not know theirs.
        $this->profile(['postal_code' => null]);

        $this->assertTrue($this->payload()['is_complete']);
    }

    // ---- What the page does with it ------------------------------------

    #[Test]
    public function the_cart_reads_the_composed_address_and_not_the_dropped_one(): void
    {
        $page = $this->actingAs($this->customer, 'web')->get('/cart')->assertOk()->getContent();

        $this->assertStringContainsString('profile.full_address', $page);
        $this->assertStringContainsString('profile.is_complete', $page);
    }

    #[Test]
    public function no_view_fills_a_panel_from_a_field_the_schema_dropped(): void
    {
        /*
         * The sweep that would have caught both of these. Each pair is a field
         * a page used to read and the field that replaced it; a page still
         * asking for the left-hand one is showing a blank where a real value
         * belongs, and saying nothing about it.
         */
        $dropped = [
            'profile.address' => 'customer_profiles.address, split into house_street/barangay/city/province',
            'customer_profile.email' => 'customer_profiles.email, now users.email',
            'customer_profile.address' => 'customer_profiles.address, split into its parts',
        ];

        $found = [];

        foreach ($this->views() as $path => $source) {
            foreach ($dropped as $read => $why) {
                // full_address ends in the same letters, so only a read of the
                // field itself counts.
                if (preg_match('/(?<![\w.])' . preg_quote($read, '/') . '(?![\w])/', $source)) {
                    $found[] = "{$path} reads {$read} -- {$why}";
                }
            }
        }

        $this->assertSame([], $found, "A page reads a field the schema no longer has:\n" . implode("\n", $found));
    }

    /** @return array<string, string> */
    private function views(): array
    {
        $views = [];

        foreach ($this->files(resource_path('views')) as $path) {
            // Comments explain these bugs on purpose and must not trip it.
            $source = preg_replace('#/\*.*?\*/|\{\{--.*?--\}\}#s', '', file_get_contents($path));

            $views[str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $path)] = $source;
        }

        $this->assertNotEmpty($views, 'No views were read, so this test proved nothing.');

        return $views;
    }

    private function files(string $directory): array
    {
        $found = [];

        foreach (scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($path)) {
                $found = array_merge($found, $this->files($path));
            } elseif (str_ends_with($entry, '.blade.php')) {
                $found[] = $path;
            }
        }

        return $found;
    }
}
