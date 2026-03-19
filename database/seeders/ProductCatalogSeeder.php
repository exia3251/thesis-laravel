<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'product_name' => 'Fully Synthetic Gasoline/Diesel Engine Oil SAE 5W30 API SN/CJ-4 1L',
                'brand' => 'CANROYAL',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => '1 Liter',
                'description' => 'CANROYAL fully synthetic gasoline/diesel engine oil. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Fully Synthetic Gasoline/Diesel Engine Oil SAE 5W30 API DEXOS SP 1L',
                'brand' => 'CANROYAL',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => '1 Liter',
                'description' => 'CANROYAL fully synthetic gasoline/diesel engine oil with DEXOS SP formulation. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Fully Synthetic Gasoline/Diesel Engine Oil SAE 5W30 API SN/CJ-4 4L',
                'brand' => 'CANROYAL',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => '4 Liters',
                'description' => 'CANROYAL fully synthetic gasoline/diesel engine oil in 4-liter packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Diesel Engine Oil SAE 15W40 API CI4/SJ 1L',
                'brand' => 'CANROYAL',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '1 Liter',
                'description' => 'CANROYAL diesel engine oil for routine heavy-duty use. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Diesel Engine Oil SAE 15W40 API CI4/SJ 5L',
                'brand' => 'CANROYAL',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '5 Liters',
                'description' => 'CANROYAL diesel engine oil in 5-liter packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'SAE 5W30 API CK-4/SN 1L',
                'brand' => 'PATROL',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => '1 Liter',
                'description' => 'PATROL engine oil with CK-4/SN performance rating. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'SAE 10W40 APICJ-4/SM 1L',
                'brand' => 'PATROL',
                'oil_type' => 'Semi-Synthetic',
                'viscosity_grade' => '10W40',
                'unit' => '1 Liter',
                'description' => 'PATROL engine oil for gasoline and diesel applications. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'SAE 15W40 API CH-4/SL 1L',
                'brand' => 'PATROL',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '1 Liter',
                'description' => 'PATROL engine oil in 1-liter packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'SAE 15W40 API CI-4/SL 20L',
                'brand' => 'PATROL',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '20 Liters',
                'description' => 'PATROL engine oil in 20-liter packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'SAE 15W40 API CH-4/SL 200L',
                'brand' => 'PATROL',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '200 Liters',
                'description' => 'PATROL engine oil in 200-liter drum packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Gasoline/Diesel Engine Oil 5W30 API CK-4/SP 1L',
                'brand' => 'SOLAR',
                'oil_type' => 'Synthetic',
                'viscosity_grade' => '5W30',
                'unit' => '1 Liter',
                'description' => 'SOLAR gasoline/diesel engine oil with CK-4/SP rating. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Gasoline/Diesel Engine Oil SAE 15W40 API CH-4/SL 1L',
                'brand' => 'SOLAR',
                'oil_type' => 'Mineral',
                'viscosity_grade' => '15W40',
                'unit' => '1 Liter',
                'description' => 'SOLAR gasoline/diesel engine oil in 1-liter packaging. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Antifreeze Coolant 15% PCT Green Dye 1L',
                'brand' => 'SOLAR',
                'oil_type' => 'Coolant',
                'viscosity_grade' => null,
                'unit' => '1 Liter',
                'description' => 'SOLAR antifreeze coolant with 15% PCT green dye. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Antifreeze Coolant 15% PCT Pink Dye 1L',
                'brand' => 'SOLAR',
                'oil_type' => 'Coolant',
                'viscosity_grade' => null,
                'unit' => '1 Liter',
                'description' => 'SOLAR antifreeze coolant with 15% PCT pink dye. Price and stock can be updated in the admin panel.',
            ],
            [
                'product_name' => 'Antifreeze Coolant 15% PCT Green Dye 20L',
                'brand' => 'SOLAR',
                'oil_type' => 'Coolant',
                'viscosity_grade' => null,
                'unit' => '20 Liters',
                'description' => 'SOLAR antifreeze coolant with 15% PCT green dye in 20-liter packaging. Price and stock can be updated in the admin panel.',
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::updateOrCreate(
                ['product_name' => $productData['product_name']],
                array_merge($productData, [
                    'price' => 0,
                    'reorder_level' => 10,
                ])
            );

            Inventory::firstOrCreate(
                ['product_id' => $product->product_id],
                ['quantity' => 0]
            );
        }
    }
}
