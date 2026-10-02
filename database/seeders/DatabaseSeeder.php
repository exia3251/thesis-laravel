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
            'house_street' => '123 Main Street',
            'barangay' => 'Bagumbayan',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'postal_code' => '1100',
        ]);

        // The place names the address dropdowns are built from. Reference
        // data rather than this shop's, and the same on every install.
        $this->call(PsgcLocationSeeder::class);

        $this->call(ProductCatalogSeeder::class);

        /*
         * What the assistant knows, and the oil guide behind its
         * recommendations.
         *
         * These were left out, so a fresh clone came up with an empty
         * chat_intents table: the assistant had nothing to match a question
         * against and nothing to fall back on when Groq is unreachable, which
         * on a machine with no API key is always. It answered nothing and
         * read as broken. Both seeders write with updateOrCreate, so running
         * them again on a working install changes nothing.
         */
        $this->call(ChatIntentSeeder::class);
        $this->call(VehicleSpecSeeder::class);

        /*
         * Points each product at its photograph.
         *
         * The catalogue seeder writes no image_path, so a fresh install had
         * eighty products that all rendered as "No Image" -- while the
         * photographs themselves sat in storage, committed to the repository
         * and reachable, with nothing in the database pointing at them.
         *
         * This was kept out for needing the network, which was true when the
         * files were fetched from the suppliers every time. They are in the
         * repository now, and the seeder skips any file it already has, so on
         * a clone it downloads nothing and only writes the paths. A file that
         * is genuinely missing is still fetched, and still gives up after
         * thirty seconds rather than failing the seed.
         */
        $this->call(ProductImageSeeder::class);
    }
}
