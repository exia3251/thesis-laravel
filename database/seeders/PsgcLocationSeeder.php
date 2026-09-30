<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads the Philippine Statistics Authority's place names.
 *
 * database/data/psgc.csv holds all 43,764 rows, built from the PSGC API
 * (https://psgc.gitlab.io/api/, a mirror of the PSA's own publication) and
 * committed so that seeding works with no internet and gives the same result
 * on every machine -- which matters when the panel has to be able to rebuild
 * this system from the repository.
 *
 * Inserted in chunks rather than row by row: 43,764 individual inserts take
 * minutes, and the whole file goes in under two seconds this way.
 */
class PsgcLocationSeeder extends Seeder
{
    /** Big enough to be fast, small enough to stay under the placeholder limit. */
    private const CHUNK = 1000;

    public function run(): void
    {
        $path = database_path('data/psgc.csv');

        if (! is_readable($path)) {
            throw new RuntimeException(
                "The place-name list is missing at {$path}. Address dropdowns cannot be seeded without it."
            );
        }

        // Rebuilt wholesale. Nothing here is edited in the back office, so
        // there is nothing to preserve, and a partial list is worse than none.
        DB::table('psgc_locations')->delete();

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);

        $expected = ['code', 'name', 'level', 'parent_code'];

        if ($header !== $expected) {
            fclose($handle);

            throw new RuntimeException(
                'The place-name list does not have the columns expected: ' . implode(', ', $expected)
            );
        }

        $batch = [];
        $total = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || count($row) < 4) {
                continue;
            }

            $batch[] = [
                'code' => $row[0],
                'name' => $row[1],
                'level' => $row[2],
                // A province sits inside nothing.
                'parent_code' => $row[3] === '' ? null : $row[3],
            ];

            if (count($batch) >= self::CHUNK) {
                DB::table('psgc_locations')->insert($batch);
                $total += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('psgc_locations')->insert($batch);
            $total += count($batch);
        }

        fclose($handle);

        $this->command?->info("Seeded {$total} places from the Philippine Standard Geographic Code.");
    }
}
