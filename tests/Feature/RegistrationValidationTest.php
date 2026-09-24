<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * What registration accepts, and whether it agrees with the database.
 *
 * The length rules matter beyond tidiness: a field validated longer than its
 * column throws SQLSTATE 22001 from MySQL, which reaches the customer as a
 * 500 rather than as a message about the field they typed.
 */
class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return array_merge([
            'email' => 'new.customer@raney.test',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
            'full_name' => 'Ramon Villanueva',
            'phone' => '09171234567',
            'house_street' => '12 Mabini Street',
            'barangay' => 'San Roque',
            'city' => 'Batangas City',
            'province' => 'Batangas',
            'postal_code' => '4200',
        ], $overrides);
    }

    /**
     * field => the column it is written to, so a rule that outgrew its
     * column is caught here rather than by MySQL at 3am.
     */
    public static function lengths(): array
    {
        return [
            'email'        => ['email', 'users', 'email', 150],
            'full name'    => ['full_name', 'users', 'full_name', 100],
            'house street' => ['house_street', 'customer_profiles', 'house_street', 160],
            'barangay'     => ['barangay', 'customer_profiles', 'barangay', 100],
            'city'         => ['city', 'customer_profiles', 'city', 100],
            'province'     => ['province', 'customer_profiles', 'province', 100],
        ];
    }

    #[Test]
    #[DataProvider('lengths')]
    public function a_field_is_never_validated_longer_than_its_column(string $field, string $table, string $column, int $rule): void
    {
        $width = DB::selectOne(
            'SELECT CHARACTER_MAXIMUM_LENGTH AS width FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column]
        )->width;

        $this->assertLessThanOrEqual(
            (int) $width,
            $rule,
            "Registration allows {$rule} characters for {$field}, but {$table}.{$column} holds {$width}."
        );
    }

    #[Test]
    public function it_registers_a_customer_whose_details_are_all_valid(): void
    {
        $this->postJson('/shop/register', $this->form())->assertOk();

        $this->assertSame(1, User::where('email', 'new.customer@raney.test')->count());
    }

    public static function rejections(): array
    {
        return [
            'no email'             => [['email' => '']],
            'email that is not one' => [['email' => 'not-an-address']],
            'email past the column' => [['email' => str_repeat('a', 145) . '@raney.test']],
            'name past the column'  => [['full_name' => str_repeat('A', 101)]],
            'digits in the name'    => [['full_name' => 'Ramon 123']],
            'a name that is a tag'  => [['full_name' => '<script>alert(1)</script>']],
            'password too short'    => [['password' => 'short1', 'password_confirmation' => 'short1']],
            'password unconfirmed'  => [['password_confirmation' => 'something else']],
            'password past bcrypt'  => [[
                'password' => str_repeat('a', PasswordPolicy::MAXIMUM + 1),
                'password_confirmation' => str_repeat('a', PasswordPolicy::MAXIMUM + 1),
            ]],
            'phone that is a landline' => [['phone' => '0281234567']],
            'phone with letters'    => [['phone' => '09abcdefghi']],
            'street past the column' => [['house_street' => str_repeat('a', 161)]],
            'barangay past the column' => [['barangay' => str_repeat('a', 101)]],
            'postal code of five digits' => [['postal_code' => '12345']],
        ];
    }

    #[Test]
    #[DataProvider('rejections')]
    public function it_refuses_a_registration_that_does_not_fit(array $overrides): void
    {
        $this->postJson('/shop/register', $this->form($overrides))->assertStatus(422);

        $this->assertSame(0, User::where('role', User::ROLE_CUSTOMER)->count());
    }

    #[Test]
    public function a_password_that_can_be_registered_can_also_be_changed_to(): void
    {
        // These were 100 and 32, so a long password could be registered and
        // then never changed to anything of the same length.
        $registration = (new \ReflectionClass(\App\Http\Controllers\AuthController::class))
            ->newInstanceWithoutConstructor();

        $rules = (fn () => $this->customerRegistrationRules())->call($registration);

        $this->assertSame(
            PasswordPolicy::rules(),
            $rules['password'],
            'Registration should use the same password policy as every other screen.'
        );
    }
}
