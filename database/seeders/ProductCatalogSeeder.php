<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * The lines RANEY LUBRICANTS TRADING actually carries.
 *
 * Product names and specifications were checked against the manufacturers'
 * own catalogues on 24 September 2026:
 *
 *   Canroyal  https://canroyallubricant.com/our-products/
 *   Solar     https://www.solarlubricants.com/
 *
 * Two things that came out of that check and are worth knowing before
 * anyone edits this file:
 *
 *  - Solar's motorcycle 10W40 is published as JASO MA. It is not MB and not
 *    MA2, and it could not be all three: MA is for wet clutches and MB is
 *    for the low-friction scooter case, so one oil cannot claim both.
 *
 *  - Solar sells coolant by concentration with a colour option, not as a
 *    named "green" or "pink" product. Green and Pink below are the colour,
 *    and the concentration is the business's to set.
 *
 * PRICES BELOW ARE PLACEHOLDERS. They are generated from a per-litre base
 * and a pack multiplier so that the ladder is at least internally coherent,
 * which the old catalogue was not -- it priced 200L at PHP 5.70 a litre
 * against PHP 470 a litre for 1L. Replace them with the real price list.
 */
class ProductCatalogSeeder extends Seeder
{
    /**
     * Pack sizes, and what one unit of each costs relative to a litre.
     *
     * A drum is stocked only on 5W30 and 15W40, which is where the volume
     * trade is; the rest are sold in bottles.
     */
    private const PACKS = [
        '1L'   => ['unit' => '1 Liter',    'litres' => 1,   'multiplier' => 1.0],
        '4L'   => ['unit' => '4 Liters',   'litres' => 4,   'multiplier' => 3.7],
        '5L'   => ['unit' => '5 Liters',   'litres' => 5,   'multiplier' => 4.5],
        'DRUM' => ['unit' => '200 Liters', 'litres' => 200, 'multiplier' => 160.0],
    ];

    public function run(): void
    {
        foreach ($this->lines() as $line) {
            foreach ($line['packs'] as $packKey) {
                $pack = self::PACKS[$packKey];

                $product = Product::create([
                    // No pack size in the name. The size lives in `unit`, and
                    // product_line ties the sizes of one oil together so the
                    // shop shows a single card with a size selector on it.
                    'product_name'    => $line['name'],
                    'product_line'    => $line['key'],
                    'brand'           => $line['brand'],
                    'oil_type'        => $line['oil_type'],
                    'viscosity_grade' => $line['viscosity'],
                    'unit'            => $pack['unit'],
                    'price'           => round($line['per_litre'] * $pack['multiplier'], 2),
                    'reorder_level'   => $packKey === 'DRUM' ? 2 : 10,
                    'description'     => $line['description'],
                ]);

                Inventory::create([
                    'product_id'   => $product->product_id,
                    // Patrol is being withdrawn by its manufacturer, so it is
                    // listed at zero rather than hidden: a customer looking
                    // for it should see that we carried it and it is gone.
                    'quantity'     => ($line['discontinued'] ?? false) ? 0 : ($packKey === 'DRUM' ? 4 : 40),
                    'last_updated' => now(),
                ]);
            }
        }
    }

    /**
     * One entry per product line. Pack sizes expand into separate rows.
     */
    private function lines(): array
    {
        return [

            // ------------------------------------------------------ CANROYAL
            [
                'key' => 'canroyal-5w30',
                'name' => 'Canroyal Full Synthetic Gasoline Engine Oil SAE 5W30 API SN',
                'brand' => 'CANROYAL', 'oil_type' => 'Synthetic', 'viscosity' => '5W30',
                'per_litre' => 650, 'packs' => ['1L', '4L', '5L', 'DRUM'],
                'description' => 'Full synthetic gasoline engine oil meeting API SN. Suits modern petrol engines where the handbook calls for a 5W30.',
            ],
            [
                'key' => 'canroyal-15w40',
                'name' => 'Canroyal Full Synthetic Diesel Engine Oil SAE 15W40 API CI-4',
                'brand' => 'CANROYAL', 'oil_type' => 'Synthetic', 'viscosity' => '15W40',
                'per_litre' => 480, 'packs' => ['1L', '4L', '5L', 'DRUM'],
                'description' => 'Full synthetic heavy duty diesel engine oil meeting API CI-4, for trucks and equipment worked hard.',
            ],
            [
                'key' => 'canroyal-10w30',
                'name' => 'Canroyal Semi Synthetic Engine Oil SAE 10W30 API SM',
                'brand' => 'CANROYAL', 'oil_type' => 'Semi-Synthetic', 'viscosity' => '10W30',
                'per_litre' => 420, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Semi synthetic engine oil meeting API SM, for older petrol engines and mixed fleets.',
            ],
            [
                'key' => 'canroyal-atf-dex3',
                'name' => 'Canroyal Semi Synthetic Automatic Transmission Fluid DEXRON-III',
                'brand' => 'CANROYAL', 'oil_type' => 'Semi-Synthetic', 'viscosity' => 'ATF',
                'per_litre' => 380, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Automatic transmission fluid meeting General Motors DEXRON-III. Back-serviceable where DEXRON-II or III is specified, and suitable for power steering units.',
            ],
            [
                'key' => 'canroyal-atf-dex6',
                'name' => 'Canroyal Full Synthetic Automatic Transmission Fluid DEXRON-VI',
                'brand' => 'CANROYAL', 'oil_type' => 'Synthetic', 'viscosity' => 'ATF',
                'per_litre' => 520, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Full synthetic automatic transmission fluid meeting DEXRON-VI, for transmissions that call for the later specification.',
            ],

            // --------------------------------------------------------- SOLAR
            [
                'key' => 'solar-5w30',
                'name' => 'Solar Premium Series Motor Engine Oil 5W30 API SN/CF',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => '5W30',
                'per_litre' => 640, 'packs' => ['1L', '4L', '5L', 'DRUM'],
                'description' => 'Premium series 5W30 meeting API SN/CF, for petrol engines and light diesels.',
            ],
            [
                'key' => 'solar-15w40',
                'name' => 'Solar Premium Series Diesel Engine Oil 15W40 API CK-4',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => '15W40',
                'per_litre' => 500, 'packs' => ['1L', '4L', '5L', 'DRUM'],
                'description' => 'Premium series heavy duty diesel oil meeting API CK-4, the current heavy duty category.',
            ],
            [
                'key' => 'solar-10w30',
                'name' => 'Solar Optima Series Motor Engine Oil 10W30 API SN',
                'brand' => 'SOLAR', 'oil_type' => 'Semi-Synthetic', 'viscosity' => '10W30',
                'per_litre' => 410, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Semi synthetic 10W30 meeting API SN, for everyday petrol engines.',
            ],
            [
                // Left as Other on purpose. Solar's page says "premium
                // quality base oils" and stops there, so unlike the other
                // three transmission fluids there is nothing to call this.
                'key' => 'solar-atf-dex3',
                'name' => 'Solar Premium Series Automatic Transmission Fluid API DEX III',
                'brand' => 'SOLAR', 'oil_type' => 'Other', 'viscosity' => 'ATF',
                'per_litre' => 370, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Automatic transmission fluid to the DEXRON-III specification.',
            ],
            [
                'key' => 'solar-atf-dex6',
                'name' => 'Solar Premium Series Automatic Transmission Fluid DEXRON VI / MERCON LV',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => 'ATF',
                'per_litre' => 510, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Automatic transmission fluid meeting DEXRON VI and MERCON LV.',
            ],
            [
                'key' => 'solar-coolant-green',
                'name' => 'Solar Antifreeze Coolant Green',
                'brand' => 'SOLAR', 'oil_type' => 'Coolant', 'viscosity' => null,
                'per_litre' => 220, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Ethylene glycol antifreeze coolant, green. Confirm the concentration you need before ordering; ask our staff if you are unsure.',
            ],
            [
                'key' => 'solar-coolant-pink',
                'name' => 'Solar Antifreeze Coolant Pink',
                'brand' => 'SOLAR', 'oil_type' => 'Coolant', 'viscosity' => null,
                'per_litre' => 240, 'packs' => ['1L', '4L', '5L'],
                'description' => 'Ethylene glycol antifreeze coolant, pink. Confirm the concentration you need before ordering; ask our staff if you are unsure.',
            ],
            [
                // The manufacturer publishes JASO MA for this oil. Not MB,
                // which is the opposite friction case, and not MA2.
                // UNCONFIRMED base type. Solar's page says "highly refined
                // base stock", then that the oil is "available fully
                // synthetic, synthetic blend and mineral", which is three
                // answers rather than one. Which variant RLT imports decides
                // this, so confirm it with them before the listing is trusted.
                'key' => 'solar-moto-10w40',
                'name' => 'Solar Optima Series Motorcycle Engine Oil 10W40 API SL JASO MA',
                'brand' => 'SOLAR', 'oil_type' => 'Semi-Synthetic', 'viscosity' => '10W40',
                'per_litre' => 320, 'packs' => ['1L'],
                'description' => 'Four-stroke motorcycle engine oil meeting API SL and JASO MA, so it suits wet clutches. Not a JASO MB oil; check your handbook if your scooter calls for MB.',
            ],

            // -------------------------------------------------------- PATROL
            [
                'key' => 'patrol-5w30',
                'name' => 'Patrol Fully Synthetic Engine Oil SAE 5W30 API CK-4/SN',
                'brand' => 'PATROL', 'oil_type' => 'Synthetic', 'viscosity' => '5W30',
                'per_litre' => 580, 'packs' => ['1L', '4L', '5L'],
                'discontinued' => true,
                'description' => 'Fully synthetic 5W30 meeting API CK-4/SN. Patrol is being withdrawn by the manufacturer, so this line is out of stock and is not being replenished.',
            ],
        ];
    }
}
