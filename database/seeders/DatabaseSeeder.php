<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\Supplier;
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

        // Create suppliers
        $supplier1 = Supplier::create([
            'supplier_name' => 'Shell Philippines',
            'contact_person' => 'Maria Santos',
            'phone' => '02-8888-8888',
            'email' => 'shell@example.com',
            'address' => 'Makati City, Metro Manila'
        ]);

        $supplier2 = Supplier::create([
            'supplier_name' => 'Petron Corporation',
            'contact_person' => 'Juan Cruz',
            'phone' => '02-7777-7777',
            'email' => 'petron@example.com',
            'address' => 'Pasig City, Metro Manila'
        ]);

        $supplier3 = Supplier::create([
            'supplier_name' => 'Caltex',
            'contact_person' => 'Pedro Reyes',
            'phone' => '02-6666-6666',
            'email' => 'caltex@example.com',
            'address' => 'Taguig City, Metro Manila'
        ]);

        echo "✅ 3 Suppliers created\n";

        // Create sample products with inventory
        $products = [
            [
                'product_name' => 'Shell Helix Ultra 5W-40 (1L)',
                'brand' => 'Shell',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W-40',
                'unit' => '1 Liter',
                'price' => 850.00,
                'reorder_level' => 10,
                'supplier_id' => $supplier1->supplier_id,
                'description' => 'Fully synthetic motor oil',
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
                'supplier_id' => $supplier1->supplier_id,
                'description' => 'Fully synthetic motor oil',
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
                'supplier_id' => $supplier2->supplier_id,
                'description' => 'High performance semi-synthetic oil',
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
                'supplier_id' => $supplier2->supplier_id,
                'description' => 'High performance semi-synthetic oil',
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
                'supplier_id' => $supplier3->supplier_id,
                'description' => 'Conventional mineral oil',
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
                'supplier_id' => $supplier3->supplier_id,
                'description' => 'Conventional mineral oil',
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
                'supplier_id' => $supplier1->supplier_id,
                'description' => 'Heavy duty diesel engine oil',
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
                'supplier_id' => $supplier2->supplier_id,
                'description' => 'Advanced fuel economy oil',
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
        echo "⚠️  SECURITY NOTE:\n";
        echo "Passwords are now HASHED in the database!\n";
        echo "You can verify in phpMyAdmin - passwords will look like:\n";
        echo "\$2y\$12\$abcd1234... (bcrypt hash)\n";
        echo "========================================\n";
    }
}
