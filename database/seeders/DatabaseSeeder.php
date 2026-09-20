<?php

namespace Database\Seeders;

use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@raney.test'], [
            'password' => 'admin123',
            'full_name' => 'System Administrator',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'inventory@raney.test'], [
            'password' => 'inventory123',
            'full_name' => 'Inventory Staff',
            'role' => User::ROLE_INVENTORY_STAFF,
            'is_active' => true,
        ]);

        User::updateOrCreate(['email' => 'accounting@raney.test'], [
            'password' => 'accounting123',
            'full_name' => 'Accounting Staff',
            'role' => User::ROLE_ACCOUNTING,
            'is_active' => true,
        ]);

        $customer = User::updateOrCreate(['email' => 'john@example.com'], [
            'password' => 'customer123',
            'full_name' => 'John Doe',
            'role' => User::ROLE_CUSTOMER,
            'is_active' => true,
        ]);

        CustomerProfile::updateOrCreate([
            'user_id' => $customer->user_id,
        ], [
            'phone' => '09123456789',
            'address' => '123 Main Street, Quezon City, Metro Manila',
        ]);

        $this->call(ProductCatalogSeeder::class);
    }
}
