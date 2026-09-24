<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fetches each product line's photo from the manufacturer that makes it.
 *
 *     php artisan db:seed --class=ProductImageSeeder
 *
 * storage/ is gitignored, so a fresh clone has no product images and the
 * whole catalogue renders as "No Image". This is how they come back, rather
 * than the files having to be passed around by hand.
 *
 * Needs an internet connection. It is not part of DatabaseSeeder for that
 * reason: seeding a database should not fail because a supplier's website is
 * down. Already-downloaded files are left alone.
 *
 * The photographs belong to Canroyal and Solar. RANEY LUBRICANTS TRADING is
 * their Philippine distributor, which is the usual basis for using a
 * supplier's product imagery, but it is worth confirming with each of them.
 */
class ProductImageSeeder extends Seeder
{
    private const CANROYAL = 'https://canroyallubricant.com/wp-content/uploads/';
    private const SOLAR = 'https://www.solarlubricants.com/wp-content/uploads/';

    /**
     * product_line => ['main' => url, 'alt' => url|null]
     *
     * `main` is what the catalogue card and the cart row show, so it wants
     * one bottle, upright, filling the frame. Canroyal publish that as their
     * -AR-FRONT image; their -AR-ALL group shots are several bottles at an
     * angle, which shrink to a blue smudge in a card and were unreadable in
     * the cart.
     *
     * `alt` is the extra picture on the product page. Canroyal's group shot
     * earns its place there because it shows the pack sizes side by side.
     *
     * Solar publish one square photograph per product that already reads
     * well small, so their lines have no alt.
     */
    private function sources(): array
    {
        return [
            'canroyal-5w30' => [
                'main' => self::CANROYAL . '2024/01/CRL-FS-G-5W30-SN-CRL-1L-AR-FRONT-1.png',
                'alt'  => self::CANROYAL . '2024/01/CRL-FS-G-5W30-SN-CRL-1L-AR-ALL-1-800x450.png',
            ],
            'canroyal-15w40' => [
                'main' => self::CANROYAL . '2024/01/CRL-FS-D-15W40-CI-4-ADNOCK-1L-AR-FRONT.png',
                'alt'  => self::CANROYAL . '2024/01/CRL-FS-D-15W40-CI-4-ADNOCK-1L-AR-ALL-800x450.png',
            ],
            'canroyal-10w30' => [
                'main' => self::CANROYAL . '2024/01/CRL-SS-G-10W30-SM-CRL-1L-AR-FRONT.png',
                'alt'  => self::CANROYAL . '2024/01/CRL-SS-G-10W30-SM-CRL-1L-AR-ALL-800x450.png',
            ],
            'canroyal-atf-dex3' => [
                'main' => self::CANROYAL . '2024/01/CRL-M-ATF-A-ADNOCK-1L-AR-FRONT-1.png',
                'alt'  => self::CANROYAL . '2024/01/CRL-M-ATF-A-ADNOCK-1L-AR-ALL-2-800x450.png',
            ],
            'canroyal-atf-dex6' => [
                'main' => self::CANROYAL . '2024/01/CRL-FS-ATF-DVI-CRL-1L-AR-FRONT.png',
                'alt'  => self::CANROYAL . '2024/01/CRL-FS-ATF-DVI-CRL-1L-AR-ALL-800x450.png',
            ],

            'solar-5w30'          => ['main' => self::SOLAR . '2024/08/MOTOR-ENGINE-OIL-PREMIUM-5W30.jpg'],
            'solar-15w40'         => ['main' => self::SOLAR . '2025/04/15w40.jpg'],
            'solar-10w30'         => ['main' => self::SOLAR . '2024/08/MOTOR-ENGINE-OIL-OPTIMA-10W30.jpg'],
            'solar-atf-dex3'      => ['main' => self::SOLAR . '2023/10/ATF-PREMIUM-DEX-III.jpg'],
            'solar-atf-dex6'      => ['main' => self::SOLAR . '2025/04/Dexron-VI-Fully-synthetic-1L-4L.jpg'],
            'solar-coolant-green' => ['main' => self::SOLAR . '2023/10/ANTIFREEZE-COOLANT-4-LTR-Front-green-cap.jpg'],
            'solar-coolant-blue'  => ['main' => self::SOLAR . '2023/10/ANTIFREEZE-COOLANT-4-LTR-Front-Blue-cap.jpg'],
            'solar-moto-10w40'    => ['main' => self::SOLAR . '2023/10/4T-MOTOR-CYCLE-ENGINE-OIL-10W40-1LTR-Front-1.jpg'],

            // Deliberately absent:
            //
            //   patrol-5w30  No manufacturer site to take one from, and the
            //     line is being withdrawn.
        ];
    }

    public function run(): void
    {
        $done = $skipped = $failed = 0;

        foreach ($this->sources() as $line => $urls) {
            $columns = [];

            foreach (['main' => 'image_path', 'alt' => 'image_path_2'] as $slot => $column) {
                $url = $urls[$slot] ?? null;

                if (!$url) {
                    continue;
                }

                $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
                $path = 'products/' . $line . ($slot === 'alt' ? '-alt' : '') . '.' . $extension;

                if (Storage::disk('public')->exists($path)) {
                    $columns[$column] = $path;
                    $skipped++;
                    continue;
                }

                $body = $this->download($url);

                if ($body === null) {
                    $this->command?->warn("  could not fetch the {$slot} image for {$line}");
                    $failed++;
                    continue;
                }

                Storage::disk('public')->put($path, $body);
                $columns[$column] = $path;
                $done++;
            }

            if ($columns) {
                // Every pack of the line, so the 4L page is not blank merely
                // because the shop card read its picture off the 1L.
                Product::where('product_line', $line)->update($columns);
            }
        }

        $this->command?->info("Downloaded {$done}, already had {$skipped}, failed {$failed}.");

        $missing = Product::whereNull('image_path')->distinct()->pluck('product_line')->filter()->implode(', ');

        if ($missing) {
            $this->command?->line("No image for: {$missing}");
        }
    }

    private function download(string $url): ?string
    {
        $handle = curl_init($url);

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'RaneyLubricants/1.0 (catalogue image sync)',
        ]);

        $body = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);

        // The content type is checked because a site that has moved its media
        // answers 200 with an HTML error page, and writing that to a .png
        // would leave a broken image nobody notices until a demo.
        return ($status === 200 && $body && str_starts_with($type, 'image/')) ? $body : null;
    }
}
