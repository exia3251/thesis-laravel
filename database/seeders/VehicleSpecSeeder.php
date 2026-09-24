<?php

namespace Database\Seeders;

use App\Models\VehicleSpec;
use Illuminate\Database\Seeder;

/**
 * Common vehicles on Philippine roads and the oil grade each takes.
 *
 * EVERY ROW HERE IS UNVERIFIED. The figures are general reference values for
 * these engines, not readings from the manufacturers' own manuals, and
 * specifications vary by model year, market and engine code more than a list
 * this size can capture.
 *
 * Putting the wrong viscosity in an engine causes real damage, so nothing
 * here is presented to a customer as settled: the assistant always says to
 * confirm against the owner's handbook, and Admin -> Assistant shows which
 * rows a member of staff has checked and signed off. Verify a row and its
 * source before relying on it commercially.
 *
 * Re-running this refreshes rows that are still unverified and leaves any a
 * member of staff has already corrected alone.
 */
class VehicleSpecSeeder extends Seeder
{
    private const SOURCE = 'General reference - confirm against the owner\'s manual';

    public function run(): void
    {
        foreach ($this->specs() as $spec) {
            $key = [
                'make' => $spec['make'],
                'model' => $spec['model'],
                'variant' => $spec['variant'] ?? null,
            ];

            $existing = VehicleSpec::where($key)->first();

            // Never overwrite a figure somebody here has checked.
            if ($existing && $existing->is_verified) {
                continue;
            }

            VehicleSpec::updateOrCreate($key, $spec + [
                'source' => self::SOURCE,
                'is_verified' => false,
            ]);
        }
    }

    /**
     * Ordered by make. Capacities are engine oil with a filter change, in
     * litres, which is what decides the pack size someone should buy.
     */
    private function specs(): array
    {
        return [
            // ------------------------------------------------------ Toyota
            ['make' => 'Toyota', 'model' => 'Vios', 'variant' => '1.3 / 1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2013, 'year_to' => 2018, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.7],

            ['make' => 'Toyota', 'model' => 'Vios', 'variant' => '1.3 / 1.5 Gasoline (2019 on)', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.4],

            ['make' => 'Toyota', 'model' => 'Wigo', 'variant' => '1.0 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2014, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.0],

            ['make' => 'Toyota', 'model' => 'Innova', 'variant' => '2.8 Diesel', 'fuel' => 'diesel',
             'year_from' => 2016, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.5],

            ['make' => 'Toyota', 'model' => 'Innova', 'variant' => '2.5 Diesel (2KD)', 'fuel' => 'diesel',
             'year_from' => 2005, 'year_to' => 2015, 'viscosity' => '15W40',
             'oil_type' => 'Mineral', 'capacity_litres' => 6.7],

            ['make' => 'Toyota', 'model' => 'Fortuner', 'variant' => '2.4 / 2.8 Diesel', 'fuel' => 'diesel',
             'year_from' => 2016, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.5],

            ['make' => 'Toyota', 'model' => 'Fortuner', 'variant' => '2.5 / 3.0 Diesel (older)', 'fuel' => 'diesel',
             'year_from' => 2005, 'year_to' => 2015, 'viscosity' => '15W40',
             'oil_type' => 'Mineral', 'capacity_litres' => 7.4],

            ['make' => 'Toyota', 'model' => 'Hilux', 'variant' => '2.4 / 2.8 Diesel', 'fuel' => 'diesel',
             'year_from' => 2016, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.5],

            ['make' => 'Toyota', 'model' => 'Hilux', 'variant' => '2.5 / 3.0 Diesel (older)', 'fuel' => 'diesel',
             'year_from' => 2005, 'year_to' => 2015, 'viscosity' => '15W40',
             'oil_type' => 'Mineral', 'capacity_litres' => 7.4],

            ['make' => 'Toyota', 'model' => 'Corolla Altis', 'variant' => '1.6 / 1.8 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'altis, corolla', 'year_from' => 2014, 'viscosity' => '5W30', 'viscosity_alt' => '0W20',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.2],

            ['make' => 'Toyota', 'model' => 'Rush', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2018, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.7],

            ['make' => 'Toyota', 'model' => 'Avanza', 'variant' => '1.3 / 1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2012, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.7],

            ['make' => 'Toyota', 'model' => 'Hiace', 'variant' => '2.5 / 3.0 Diesel', 'fuel' => 'diesel',
             'aliases' => 'hi-ace, commuter, grandia', 'year_from' => 2005, 'viscosity' => '15W40', 'viscosity_alt' => '5W30',
             'oil_type' => 'Mineral', 'capacity_litres' => 7.0],

            ['make' => 'Toyota', 'model' => 'Raize', 'variant' => '1.0 Turbo / 1.2 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2022, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.0],

            // -------------------------------------------------- Mitsubishi
            ['make' => 'Mitsubishi', 'model' => 'Mirage', 'variant' => '1.2 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2012, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.3],

            ['make' => 'Mitsubishi', 'model' => 'Mirage G4', 'variant' => '1.2 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'mirage g4, g4', 'year_from' => 2014, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.3],

            ['make' => 'Mitsubishi', 'model' => 'Montero Sport', 'variant' => '2.4 Diesel (4N15)', 'fuel' => 'diesel',
             'aliases' => 'montero, monterosport, montero sports', 'year_from' => 2016,
             'viscosity' => '5W30', 'viscosity_alt' => '15W40', 'oil_type' => 'Synthetic', 'capacity_litres' => 6.0],

            ['make' => 'Mitsubishi', 'model' => 'Montero Sport', 'variant' => '2.5 Diesel (4D56)', 'fuel' => 'diesel',
             'aliases' => 'montero, monterosport', 'year_from' => 2009, 'year_to' => 2015,
             'viscosity' => '15W40', 'oil_type' => 'Mineral', 'capacity_litres' => 6.5],

            ['make' => 'Mitsubishi', 'model' => 'Strada', 'variant' => '2.4 Diesel', 'fuel' => 'diesel',
             'aliases' => 'triton', 'year_from' => 2015, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 6.0],

            ['make' => 'Mitsubishi', 'model' => 'Xpander', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2018, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.4],

            ['make' => 'Mitsubishi', 'model' => 'L300', 'variant' => '2.2 / 2.5 Diesel', 'fuel' => 'diesel',
             'aliases' => 'l-300, l 300, fb', 'viscosity' => '15W40',
             'oil_type' => 'Mineral', 'capacity_litres' => 5.5],

            ['make' => 'Mitsubishi', 'model' => 'Adventure', 'variant' => '2.5 Diesel', 'fuel' => 'diesel',
             'viscosity' => '15W40', 'oil_type' => 'Mineral', 'capacity_litres' => 6.0],

            // ------------------------------------------------------- Honda
            ['make' => 'Honda', 'model' => 'City', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2014, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.4],

            ['make' => 'Honda', 'model' => 'Civic', 'variant' => '1.5 Turbo / 1.8 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2016, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.7],

            ['make' => 'Honda', 'model' => 'BR-V', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'brv, br v', 'year_from' => 2016, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.4],

            ['make' => 'Honda', 'model' => 'CR-V', 'variant' => '2.0 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'crv, cr v', 'year_from' => 2017, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.2],

            ['make' => 'Honda', 'model' => 'Brio', 'variant' => '1.2 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.0],

            ['make' => 'Honda', 'model' => 'Jazz', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2015, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.4],

            // ------------------------------------------------------ Nissan
            ['make' => 'Nissan', 'model' => 'Navara', 'variant' => '2.5 Diesel', 'fuel' => 'diesel',
             'year_from' => 2015, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.0],

            ['make' => 'Nissan', 'model' => 'Terra', 'variant' => '2.5 Diesel', 'fuel' => 'diesel',
             'year_from' => 2018, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.0],

            ['make' => 'Nissan', 'model' => 'Almera', 'variant' => '1.0 Turbo / 1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2012, 'viscosity' => '5W30', 'viscosity_alt' => '0W20',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.0],

            ['make' => 'Nissan', 'model' => 'Urvan', 'variant' => '2.5 Diesel (NV350)', 'fuel' => 'diesel',
             'aliases' => 'nv350, nv 350', 'viscosity' => '15W40', 'viscosity_alt' => '5W30',
             'oil_type' => 'Mineral', 'capacity_litres' => 7.4],

            ['make' => 'Nissan', 'model' => 'X-Trail', 'variant' => '2.0 / 2.5 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'xtrail, x trail', 'year_from' => 2015, 'viscosity' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.3],

            // -------------------------------------------------------- Ford
            ['make' => 'Ford', 'model' => 'Ranger', 'variant' => '2.2 / 3.2 Diesel', 'fuel' => 'diesel',
             'year_from' => 2015, 'year_to' => 2021, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 8.0],

            ['make' => 'Ford', 'model' => 'Ranger', 'variant' => '2.0 Bi-Turbo Diesel', 'fuel' => 'diesel',
             'year_from' => 2019, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 6.0],

            ['make' => 'Ford', 'model' => 'Everest', 'variant' => '2.0 / 2.2 Diesel', 'fuel' => 'diesel',
             'year_from' => 2015, 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 8.0],

            ['make' => 'Ford', 'model' => 'EcoSport', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'aliases' => 'eco sport, ecosports', 'viscosity' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.1],

            ['make' => 'Ford', 'model' => 'Territory', 'variant' => '1.5 Turbo Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2021, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 4.0],

            // ------------------------------------------------------- Isuzu
            ['make' => 'Isuzu', 'model' => 'D-Max', 'variant' => '2.5 / 3.0 Diesel', 'fuel' => 'diesel',
             'aliases' => 'dmax, d max', 'year_from' => 2013, 'year_to' => 2020,
             'viscosity' => '15W40', 'viscosity_alt' => '5W30', 'oil_type' => 'Mineral', 'capacity_litres' => 7.3],

            ['make' => 'Isuzu', 'model' => 'D-Max', 'variant' => '1.9 Diesel (RZ4E)', 'fuel' => 'diesel',
             'aliases' => 'dmax, d max', 'year_from' => 2021, 'viscosity' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 6.0],

            ['make' => 'Isuzu', 'model' => 'mu-X', 'variant' => '1.9 / 3.0 Diesel', 'fuel' => 'diesel',
             'aliases' => 'mux, mu x', 'year_from' => 2015, 'viscosity' => '15W40', 'viscosity_alt' => '5W30',
             'oil_type' => 'Mineral', 'capacity_litres' => 7.3],

            ['make' => 'Isuzu', 'model' => 'Crosswind', 'variant' => '2.5 Diesel (4JA1)', 'fuel' => 'diesel',
             'viscosity' => '15W40', 'oil_type' => 'Mineral', 'capacity_litres' => 6.0],

            ['make' => 'Isuzu', 'model' => 'Elf', 'variant' => '4HG1 / 4JJ1 Diesel Truck', 'fuel' => 'diesel',
             'aliases' => 'nhr, nkr, npr', 'viscosity' => '15W40',
             'oil_type' => 'Mineral', 'capacity_litres' => 10.5,
             'notes' => 'Light truck. Sold in 20 and 200 litre drums for fleets.'],

            ['make' => 'Isuzu', 'model' => 'Traviz', 'variant' => '2.5 Diesel', 'fuel' => 'diesel',
             'year_from' => 2019, 'viscosity' => '15W40', 'oil_type' => 'Mineral', 'capacity_litres' => 6.0],

            // ----------------------------------------------------- Hyundai
            ['make' => 'Hyundai', 'model' => 'Accent', 'variant' => '1.4 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2011, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.3],

            ['make' => 'Hyundai', 'model' => 'Accent', 'variant' => '1.6 CRDi Diesel', 'fuel' => 'diesel',
             'year_from' => 2011, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 5.3],

            ['make' => 'Hyundai', 'model' => 'Tucson', 'variant' => '2.0 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2016, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 4.0],

            ['make' => 'Hyundai', 'model' => 'Starex', 'variant' => '2.5 CRDi Diesel', 'fuel' => 'diesel',
             'aliases' => 'grand starex, staria', 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 8.0],

            // ------------------------------------------------------ Suzuki
            ['make' => 'Suzuki', 'model' => 'Ertiga', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '5W30', 'viscosity_alt' => '0W20',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.1],

            ['make' => 'Suzuki', 'model' => 'Swift', 'variant' => '1.2 Gasoline', 'fuel' => 'gasoline',
             'viscosity' => '5W30', 'viscosity_alt' => '0W20', 'oil_type' => 'Synthetic', 'capacity_litres' => 3.1],

            ['make' => 'Suzuki', 'model' => 'Jimny', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 3.1],

            ['make' => 'Suzuki', 'model' => 'Celerio', 'variant' => '1.0 Gasoline', 'fuel' => 'gasoline',
             'viscosity' => '5W30', 'viscosity_alt' => '0W20', 'oil_type' => 'Synthetic', 'capacity_litres' => 2.8],

            ['make' => 'Suzuki', 'model' => 'Carry', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'viscosity' => '10W40', 'viscosity_alt' => '5W30', 'oil_type' => 'Semi-Synthetic', 'capacity_litres' => 3.5],

            // --------------------------------------------------------- Kia
            ['make' => 'Kia', 'model' => 'Picanto', 'variant' => '1.0 / 1.2 Gasoline', 'fuel' => 'gasoline',
             'viscosity' => '5W30', 'viscosity_alt' => '10W40', 'oil_type' => 'Synthetic', 'capacity_litres' => 3.3],

            ['make' => 'Kia', 'model' => 'Soluto', 'variant' => '1.4 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '5W30', 'viscosity_alt' => '10W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 3.3],

            ['make' => 'Kia', 'model' => 'Seltos', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2020, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 4.0],

            // ------------------------------------------------------- Mazda
            ['make' => 'Mazda', 'model' => 'Mazda3', 'variant' => '1.5 / 2.0 Skyactiv-G', 'fuel' => 'gasoline',
             'aliases' => 'mazda 3, 3', 'year_from' => 2014, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.2],

            ['make' => 'Mazda', 'model' => 'CX-5', 'variant' => '2.0 / 2.5 Skyactiv-G', 'fuel' => 'gasoline',
             'aliases' => 'cx5, cx 5', 'year_from' => 2013, 'viscosity' => '0W20', 'viscosity_alt' => '5W30',
             'oil_type' => 'Synthetic', 'capacity_litres' => 4.8],

            ['make' => 'Mazda', 'model' => 'BT-50', 'variant' => '2.2 / 3.2 Diesel', 'fuel' => 'diesel',
             'aliases' => 'bt50, bt 50', 'viscosity' => '5W30', 'viscosity_alt' => '15W40',
             'oil_type' => 'Synthetic', 'capacity_litres' => 7.9],

            // ------------------------------------------- newer China makes
            ['make' => 'MG', 'model' => 'ZS', 'variant' => '1.5 Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2019, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 4.0],

            ['make' => 'Geely', 'model' => 'Coolray', 'variant' => '1.5 Turbo Gasoline', 'fuel' => 'gasoline',
             'year_from' => 2020, 'viscosity' => '5W30', 'oil_type' => 'Synthetic', 'capacity_litres' => 4.0,
             'notes' => 'Turbocharged. A fully synthetic oil is worth the difference here.'],

            // ------------------------------------------- commercial diesel
            ['make' => 'Mitsubishi', 'model' => 'Canter', 'variant' => '4D34 Diesel Truck', 'fuel' => 'diesel',
             'viscosity' => '15W40', 'oil_type' => 'Mineral', 'capacity_litres' => 11.0,
             'notes' => 'Light truck. Sold in 20 and 200 litre drums for fleets.'],

        ];
    }
}
