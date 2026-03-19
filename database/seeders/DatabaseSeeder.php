<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['username' => 'superadmin'], [
            'username' => 'superadmin',
            'password' => 'superadmin123',
            'full_name' => 'Super Administrator',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        User::updateOrCreate(['username' => 'admin'], [
            'username' => 'admin',
            'password' => 'admin123',
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $customer = User::updateOrCreate(['username' => 'customer'], [
            'username' => 'customer',
            'password' => 'customer123',
            'full_name' => 'John Doe',
            'role' => 'customer',
            'is_active' => true,
        ]);

        CustomerProfile::updateOrCreate([
            'user_id' => $customer->user_id,
        ], [
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'address' => '123 Main Street, Quezon City, Metro Manila',
        ]);

        $this->call(ProductCatalogSeeder::class);
    }
}
