<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Models\Inventory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user with hashed password
        $admin = User::create([
            'username' => 'admin',
            'password' => 'admin123', // Auto-hashed by User model
            'full_name' => 'System Administrator',
            'role' => 'admin'
        ]);

        echo "✅ Admin created: admin / admin123\n";

        // Create customer user with hashed password
        $customer = User::create([
            'username' => 'customer',
            'password' => 'customer123', // Auto-hashed by User model
            'full_name' => 'John Doe',
            'role' => 'customer'
        ]);

        // Create customer profile
        CustomerProfile::create([
            'user_id' => $customer->user_id,
            'phone' => '09123456789',
            'email' => 'john@example.com',
            'address' => '123 Main Street, Quezon City, Metro Manila'
        ]);

        echo "✅ Customer created: customer / customer123\n";

        // Create sample products with inventory (NO suppliers)
        $products = [
            [
                'product_name' => 'Shell Helix Ultra 5W-40 (1L)',
                'brand' => 'Shell',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W-40',
                'unit' => '1 Liter',
                'price' => 850.00,
                'reorder_level' => 10,
                'description' => 'Fully synthetic motor oil for superior engine protection',
                'stock' => 50
            ],
            [
                'product_name' => 'Shell Helix Ultra 5W-40 (4L)',
                'brand' => 'Shell',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W-40',
                'unit' => '4 Liters',
                'price' => 3200.00,
                'reorder_level' => 5,
                'description' => 'Fully synthetic motor oil - 4 liter pack for better value',
                'stock' => 30
            ],
            [
                'product_name' => 'Petron Blaze Racing 10W-40 (1L)',
                'brand' => 'Petron',
                'oil_type' => 'Semi-Synthetic',
                'viscosity_grade' => '10W-40',
                'unit' => '1 Liter',
                'price' => 450.00,
                'reorder_level' => 15,
                'description' => 'High performance semi-synthetic oil for racing enthusiasts',
                'stock' => 75
            ],
            [
                'product_name' => 'Petron Blaze Racing 10W-40 (4L)',
                'brand' => 'Petron',
                'oil_type' => 'Semi-Synthetic',
                'viscosity_grade' => '10W-40',
                'unit' => '4 Liters',
                'price' => 1700.00,
                'reorder_level' => 8,
                'description' => 'High performance semi-synthetic oil - 4 liter value pack',
                'stock' => 40
            ],
            [
                'product_name' => 'Caltex Havoline 20W-50 (1L)',
                'brand' => 'Caltex',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '20W-50',
                'unit' => '1 Liter',
                'price' => 280.00,
                'reorder_level' => 20,
                'description' => 'Conventional mineral oil for older engines',
                'stock' => 100
            ],
            [
                'product_name' => 'Caltex Havoline 20W-50 (4L)',
                'brand' => 'Caltex',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '20W-50',
                'unit' => '4 Liters',
                'price' => 1050.00,
                'reorder_level' => 10,
                'description' => 'Conventional mineral oil - 4 liter economy pack',
                'stock' => 60
            ],
            [
                'product_name' => 'Shell Rimula R6 LM 10W-40 (1L)',
                'brand' => 'Shell',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '10W-40',
                'unit' => '1 Liter',
                'price' => 650.00,
                'reorder_level' => 12,
                'description' => 'Heavy duty diesel engine oil with low emissions technology',
                'stock' => 45
            ],
            [
                'product_name' => 'Petron Ultron Fully Synthetic 0W-20 (1L)',
                'brand' => 'Petron',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '0W-20',
                'unit' => '1 Liter',
                'price' => 900.00,
                'reorder_level' => 8,
                'description' => 'Advanced fuel economy oil for modern engines',
                'stock' => 35
            ],
        ];

        foreach ($products as $productData) {
            $stock = $productData['stock'];
            unset($productData['stock']);

            $product = Product::create($productData);

            // Create inventory record
            Inventory::create([
                'product_id' => $product->product_id,
                'quantity' => $stock,
                'last_updated' => now()
            ]);
        }

        echo "✅ 8 Sample products created with inventory\n";
        echo "\n";
        echo "========================================\n";
        echo "✅ DATABASE SEEDED SUCCESSFULLY!\n";
        echo "========================================\n";
        echo "Admin Login:\n";
        echo "  URL: /admin/login\n";
        echo "  Username: admin\n";
        echo "  Password: admin123\n";
        echo "\n";
        echo "Customer Login:\n";
        echo "  URL: /shop/login\n";
        echo "  Username: customer\n";
        echo "  Password: customer123\n";
        echo "========================================\n";
        echo "\n";
        echo "📦 PRODUCTS (No Suppliers):\n";
        echo "All products now use 'brand' field only\n";
        echo "No supplier relationships needed!\n";
        echo "========================================\n";
    }
}
