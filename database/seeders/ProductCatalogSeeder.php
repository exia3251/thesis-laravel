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
 *    named product per colour. Green and Blue below are the colour, and the
 *    concentration is the business's to set. Those two are carried because
 *    they are the two Solar has published photographs of; the range also
 *    includes red and pink.
 *
 * PRICES ARE THE BUSINESS'S OWN, taken from the supplier price list supplied
 * on 26 September 2026 and read off its SRP column, which is the retail
 * price. They are written out per pack rather than generated from a per-litre
 * figure, because real prices are not a formula: a litre in a four-litre
 * bottle costs less than a litre in a one-litre bottle, and by a different
 * amount at every grade.
 *
 * The list is organised by viscosity and API grade rather than by brand, so
 * two brands meeting the same specification carry the same price here. That
 * is the list's own arrangement, not an assumption made for it.
 *
 * Where the list has no row for a pack this shop sells, the price is worked
 * out from the nearest row it does have, and the working is written beside
 * the line. Two products have no near row at all -- the coolants, since a
 * list of engine oils has nothing resembling one -- and those keep the
 * prices they had, marked as still needing the business's own figure.
 */
class ProductCatalogSeeder extends Seeder
{
    /**
     * Pack sizes, and what one unit of each costs relative to a litre.
     *
     * Drums are gone entirely, in two steps. The 5W30 pair went first: at
     * these prices they came to PHP 104,000 and PHP 102,400, and a fully
     * verified GCash wallet stops at PHP 100,000, so the site was listing
     * something no customer could pay for through the only online method it
     * offers. The 15W40 pair followed when the business stopped listing the
     * size at all.
     *
     * The three that remain are the three config('business.pack_sizes')
     * offers in the add-product form and the three the shop assistant will
     * name. A seeder that put back a fourth would have every one of those
     * disagree with the catalogue on a teammate's fresh install.
     */
    private const PACKS = [
        '1L' => ['unit' => '1 Liter',  'litres' => 1],
        '4L' => ['unit' => '4 Liters', 'litres' => 4],
        '5L' => ['unit' => '5 Liters', 'litres' => 5],
    ];

    public function run(): void
    {
        // The manufacturers' own write-ups, scraped once and committed so
        // that seeding never needs the network. See database/data.
        $copy = require database_path('data/product-copy.php');

        foreach ($this->lines() as $line) {
            $write = $copy[$line['key']] ?? null;

            // The one-line description stays hand-written: it is what a card
            // and an order email quote, and the manufacturer's opening
            // paragraph is too long for either.
            $specifications = $write ? array_filter([
                'overview'     => $write['summary'] ?? null,
                'grades'       => $write['grades'] ?? null,
                'applications' => $write['applications'] ?? null,
                'benefits'     => $write['benefits'] ?? null,
                'standards'    => $write['standards'] ?? null,
                // "Properties => 15W40" is the table's own header row, which
                // reads as nonsense once it is out of the table.
                'properties'   => array_diff_key($write['properties'] ?? [], ['Properties' => null]) ?: null,
                'source'       => $write['source'] ?? null,
            ]) : null;

            foreach (array_keys($line['prices']) as $packKey) {
                $pack = self::PACKS[$packKey];

                /*
                 * Matched on the line and the pack, so running this again
                 * updates the row it wrote last time.
                 *
                 * It used to create unconditionally, which made seeding a
                 * one-shot operation nobody could repeat: a second
                 * `php artisan db:seed` wrote the whole catalogue again, and
                 * the shop then showed every oil two, three, ten times over.
                 * Seeding is meant to be safe to re-run -- it is how a new
                 * seeder reaches an existing install.
                 *
                 * Price is deliberately part of the write rather than the key:
                 * the catalogue here is the source of truth for it.
                 */
                $product = Product::withTrashed()->updateOrCreate(
                    [
                        'product_line' => $line['key'],
                        'unit'         => $pack['unit'],
                    ],
                    [
                        // No pack size in the name. The size lives in `unit`,
                        // and product_line ties the sizes of one oil together
                        // so the shop shows a single card with a size
                        // selector on it.
                        'product_name'    => $line['name'],
                        'brand'           => $line['brand'],
                        'oil_type'        => $line['oil_type'],
                        'viscosity_grade' => $line['viscosity'],
                        'price'           => $line['prices'][$packKey],
                        'reorder_level'   => 10,
                        'description'     => $line['description'],
                        'specifications'  => $specifications,
                        // A line that was archived and is being seeded again
                        // is being put back deliberately.
                        'deleted_at'      => null,
                    ]
                );

                /*
                 * firstOrCreate, not updateOrCreate: the opening quantity is
                 * a starting point, and a shop that has been trading has a
                 * truer one. Re-seeding must not reset the shelf to forty and
                 * wipe out every stock movement since.
                 */
                Inventory::firstOrCreate(
                    ['product_id' => $product->product_id],
                    [
                        // Patrol is being withdrawn by its manufacturer, so it
                        // is listed at zero rather than hidden: a customer
                        // looking for it should see that we carried it and it
                        // is gone.
                        'quantity'     => ($line['discontinued'] ?? false) ? 0 : 40,
                        'last_updated' => now(),
                    ]
                );
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
                // List: 5W30 API SN/CJ-4 at 595 the litre bottle and 2320 the
                // four. It has no five-litre 5W30; the five-litre 5W40 beside
                // it carries the same litre price, so its 2820 is used.
                'prices' => ['1L' => 595, '4L' => 2320, '5L' => 2820],
                'description' => 'Full synthetic gasoline engine oil meeting API SN. Suits modern petrol engines where the handbook calls for a 5W30.',
            ],
            [
                'key' => 'canroyal-15w40',
                'name' => 'Canroyal Full Synthetic Diesel Engine Oil SAE 15W40 API CI-4',
                'brand' => 'CANROYAL', 'oil_type' => 'Synthetic', 'viscosity' => '15W40',
                // List: 15W40 API CI4/SJ at 365, and 15W40 at 1750 the five
                // litre. No four litre, so 355 the litre -- between the two
                // rates the list itself charges.
                //
                // The drum is 281 the litre: the list prices its fully
                // synthetic 5W30 drum at 77 per cent of that oil's own bottle
                // rate, and the same 77 per cent of this oil's bottle rate is
                // 281. Marking the nearest drum's wholesale up instead put it
                // at 406 a litre -- dearer than the five litre bottle, which
                // is not how anybody sells a drum.
                'prices' => ['1L' => 365, '4L' => 1420, '5L' => 1750],
                'description' => 'Full synthetic heavy duty diesel engine oil meeting API CI-4, for trucks and equipment worked hard.',
            ],
            [
                'key' => 'canroyal-10w30',
                'name' => 'Canroyal Semi Synthetic Engine Oil SAE 10W30 API SM',
                'brand' => 'CANROYAL', 'oil_type' => 'Semi-Synthetic', 'viscosity' => '10W30',
                // No 10W30 on the list. Nearest is 10W40 API CJ4/SM at 490,
                // the same semi-synthetic class. The larger packs follow the
                // list's own step down from one litre to four and five.
                'prices' => ['1L' => 490, '4L' => 1910, '5L' => 2320],
                'description' => 'Semi synthetic engine oil meeting API SM, for older petrol engines and mixed fleets.',
            ],
            [
                'key' => 'canroyal-atf-dex3',
                'name' => 'Canroyal Semi Synthetic Automatic Transmission Fluid DEXRON-III',
                'brand' => 'CANROYAL', 'oil_type' => 'Semi-Synthetic', 'viscosity' => 'ATF',
                // List: ATF DEXRON III at 500. Larger packs stepped as above.
                'prices' => ['1L' => 500, '4L' => 1950, '5L' => 2370],
                'description' => 'Automatic transmission fluid meeting General Motors DEXRON-III. Back-serviceable where DEXRON-II or III is specified, and suitable for power steering units.',
            ],
            [
                'key' => 'canroyal-atf-dex6',
                'name' => 'Canroyal Full Synthetic Automatic Transmission Fluid DEXRON-VI',
                'brand' => 'CANROYAL', 'oil_type' => 'Synthetic', 'viscosity' => 'ATF',
                // List: MULTI-VEHICLE ATF DEXRON VI at 645.
                'prices' => ['1L' => 645, '4L' => 2515, '5L' => 3055],
                'description' => 'Full synthetic automatic transmission fluid meeting DEXRON-VI, for transmissions that call for the later specification.',
            ],

            // --------------------------------------------------------- SOLAR
            [
                'key' => 'solar-5w30',
                'name' => 'Solar Premium Series Motor Engine Oil 5W30 API SN/CF',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => '5W30',
                // Same specification as the Canroyal 5W30, and the list prices
                // by specification rather than by brand.
                'prices' => ['1L' => 595, '4L' => 2320, '5L' => 2820],
                'description' => 'Premium series 5W30 meeting API SN/CF, for petrol engines and light diesels.',
            ],
            [
                'key' => 'solar-15w40',
                'name' => 'Solar Premium Series Diesel Engine Oil 15W40 API CK-4',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => '15W40',
                // As the Canroyal 15W40, for the same reason.
                'prices' => ['1L' => 365, '4L' => 1420, '5L' => 1750],
                'description' => 'Premium series heavy duty diesel oil meeting API CK-4, the current heavy duty category.',
            ],
            [
                'key' => 'solar-10w30',
                'name' => 'Solar Optima Series Motor Engine Oil 10W30 API SN',
                'brand' => 'SOLAR', 'oil_type' => 'Semi-Synthetic', 'viscosity' => '10W30',
                // As the Canroyal 10W30.
                'prices' => ['1L' => 490, '4L' => 1910, '5L' => 2320],
                'description' => 'Semi synthetic 10W30 meeting API SN, for everyday petrol engines.',
            ],
            [
                // Solar's page says only "premium quality base oils", so the
                // website cannot settle this one. Synthetic, confirmed by the
                // business on 24 September 2026.
                'key' => 'solar-atf-dex3',
                'name' => 'Solar Premium Series Automatic Transmission Fluid API DEX III',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => 'ATF',
                // List: ATF DEXRON III at 500.
                'prices' => ['1L' => 500, '4L' => 1950, '5L' => 2370],
                'description' => 'Automatic transmission fluid to the DEXRON-III specification.',
            ],
            [
                'key' => 'solar-atf-dex6',
                'name' => 'Solar Premium Series Automatic Transmission Fluid DEXRON VI / MERCON LV',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => 'ATF',
                // List: MULTI-VEHICLE ATF DEXRON VI at 645.
                'prices' => ['1L' => 645, '4L' => 2515, '5L' => 3055],
                'description' => 'Automatic transmission fluid meeting DEXRON VI and MERCON LV.',
            ],
            [
                'key' => 'solar-coolant-green',
                'name' => 'Solar Antifreeze Coolant Green',
                'brand' => 'SOLAR', 'oil_type' => 'Coolant', 'viscosity' => null,
                // NOT ON THE LIST. A list of engine oils holds nothing close to
                // a coolant, so estimating from it would be inventing rather
                // than estimating. These are the figures the site already
                // carried and they still need the business's own.
                'prices' => ['1L' => 220, '4L' => 814, '5L' => 990],
                'description' => 'Ethylene glycol antifreeze coolant, green. Confirm the concentration you need before ordering; ask our staff if you are unsure.',
            ],
            [
                // Blue rather than pink. Solar lists Green, Blue, Red and
                // Pink as colours but has only ever photographed the green
                // and blue caps, so pink was the one line on the site with
                // no picture of its own.
                'key' => 'solar-coolant-blue',
                'name' => 'Solar Antifreeze Coolant Blue',
                'brand' => 'SOLAR', 'oil_type' => 'Coolant', 'viscosity' => null,
                // NOT ON THE LIST, as the green. Still needs a real figure.
                'prices' => ['1L' => 240, '4L' => 888, '5L' => 1080],
                'description' => 'Ethylene glycol antifreeze coolant, blue. Confirm the concentration you need before ordering; ask our staff if you are unsure.',
            ],
            [
                // The manufacturer publishes JASO MA for this oil. Not MB,
                // which is the opposite friction case, and not MA2.
                // Solar publishes this oil as "available fully synthetic,
                // synthetic blend and mineral", so the website cannot settle
                // which one it is. RLT imports the fully synthetic variant,
                // confirmed by the business on 24 September 2026.
                'key' => 'solar-moto-10w40',
                'name' => 'Solar Optima Series Motorcycle Engine Oil 10W40 API SL JASO MA',
                'brand' => 'SOLAR', 'oil_type' => 'Synthetic', 'viscosity' => '10W40',
                // No motorcycle oil on the list. Nearest by grade is the 10W40
                // API CJ4/SM at 490. The weakest of these mappings: a JASO MA
                // motorcycle oil and a CJ-4 diesel oil are different products
                // that happen to share a viscosity. Worth confirming.
                'prices' => ['1L' => 490],
                'description' => 'Four-stroke motorcycle engine oil meeting API SL and JASO MA, so it suits wet clutches. Not a JASO MB oil; check your handbook if your scooter calls for MB.',
            ],

            // -------------------------------------------------------- PATROL
            [
                'key' => 'patrol-5w30',
                'name' => 'Patrol Fully Synthetic Engine Oil SAE 5W30 API CK-4/SN',
                'brand' => 'PATROL', 'oil_type' => 'Synthetic', 'viscosity' => '5W30',
                // As the other 5W30s.
                'prices' => ['1L' => 595, '4L' => 2320, '5L' => 2820],
                'discontinued' => true,
                'description' => 'Fully synthetic 5W30 meeting API CK-4/SN. Patrol is being withdrawn by the manufacturer, so this line is out of stock and is not being replenished.',
            ],
        ];
    }
}
