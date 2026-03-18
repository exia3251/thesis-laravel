<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Models\Inventory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::create([
            'username' => 'superadmin',
            'password' => 'superadmin123',
            'full_name' => 'Super Administrator',
            'role' => 'super_admin',
            'is_active' => true
        ]);

        // Admin
        User::create([
            'username' => 'admin',
            'password' => 'admin123',
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'is_active' => true
        ]);

        // Customer
        $customer = User::create([
            'username' => 'customer',
            'password' => 'customer123',
            'full_name' => 'John Doe',
            'role' => 'customer',
            'is_active' => true
        ]);

        CustomerProfile::create([
            'user_id' => $customer->user_id,
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'address' => '123 Main Street, Quezon City, Metro Manila'
        ]);

        // Products
        $products = [
            ['name' => 'Shell Helix Ultra 5W-40 (1L)', 'brand' => 'Shell', 'type' => 'Synthetic', 'grade' => '5W-40', 'price' => 850, 'stock' => 50],
            ['name' => 'Shell Helix Ultra 5W-40 (4L)', 'brand' => 'Shell', 'type' => 'Synthetic', 'grade' => '5W-40', 'price' => 3200, 'stock' => 30],
            ['name' => 'Petron Blaze Racing 10W-40 (1L)', 'brand' => 'Petron', 'type' => 'Semi-Synthetic', 'grade' => '10W-40', 'price' => 450, 'stock' => 75],
            ['name' => 'Petron Blaze Racing 10W-40 (4L)', 'brand' => 'Petron', 'type' => 'Semi-Synthetic', 'grade' => '10W-40', 'price' => 1700, 'stock' => 40],
            ['name' => 'Caltex Havoline 20W-50 (1L)', 'brand' => 'Caltex', 'type' => 'Mineral', 'grade' => '20W-50', 'price' => 280, 'stock' => 100],
            ['name' => 'Caltex Havoline 20W-50 (4L)', 'brand' => 'Caltex', 'type' => 'Mineral', 'grade' => '20W-50', 'price' => 1050, 'stock' => 60],
        ];

        foreach ($products as $p) {
            $product = Product::create([
                'product_name' => $p['name'],
                'brand' => $p['brand'],
                'oil_type' => $p['type'],
                'viscosity_grade' => $p['grade'],
                'unit' => strpos($p['name'], '4L') ? '4 Liters' : '1 Liter',
                'price' => $p['price'],
                'reorder_level' => 10,
                'description' => 'High quality engine oil'
            ]);

            Inventory::create([
                'product_id' => $product->product_id,
                'quantity' => $p['stock']
            ]);
        }

        echo "\n✅ Seeded: 3 users, 6 products\n";
        echo "Login: superadmin/superadmin123, admin/admin123, customer/customer123\n\n";
    }
}
