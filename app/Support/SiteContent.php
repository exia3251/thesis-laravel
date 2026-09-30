<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * What the storefront says about the business, and where it comes from.
 *
 * Every value has two possible sources: a row the administrator has edited,
 * and the default in config/business.php. The row wins when it exists, and
 * the default is used when it does not -- so a fresh install shows the same
 * details it always did, and nothing has to be filled in before the system
 * works.
 *
 * That fallback is the whole design. An editor that must be populated before
 * the site reads correctly is an editor that breaks the site the first time
 * somebody clears a field.
 */
final class SiteContent
{
    private const CACHE_KEY = 'site.content';

    /** Every text field the back office may edit, and where it falls back to. */
    public const TEXT_FIELDS = [
        'name'     => 'business.name',
        'tagline'  => null,
        'address'  => 'business.address',
        'phone'    => 'business.phone',
        'email'    => 'business.email',
        'hours'    => 'business.hours',
        'facebook' => 'business.facebook',
    ];

    /**
     * The pictures, with the shape each one has to be.
     *
     * The ratio is what the cropper holds the selection to, and the width and
     * height are what it writes out. A QR code is square and is given room
     * rather than being cropped tight: trimming its quiet zone stops some
     * readers scanning it at all.
     */
    public const IMAGE_FIELDS = [
        'logo' => [
            'label' => 'Logo',
            'ratio' => 1,
            'width' => 512,
            'height' => 512,
            'note' => 'Shown in place of the RANEY LUBRICANTS wording across the shop. Square.',
        ],
        'banner' => [
            'label' => 'Hero banner',
            'ratio' => 8 / 3,
            'width' => 1600,
            'height' => 600,
            'note' => 'The picture behind the heading on the shop home page. Wide.',
        ],
        /*
         * Portrait, not square.
         *
         * A GCash code is saved from the app as a tall picture with the
         * account name and number under the code, and the page that shows it
         * already carries a note explaining that squaring it crops the code
         * off and a cropped code does not scan. Offering a square frame here
         * would have walked straight back into that.
         */
        'gcash_qr' => [
            'label' => 'GCash QR code',
            'ratio' => 4 / 5,
            'width' => 800,
            'height' => 1000,
            'note' => 'Shown to customers paying by GCash. Keep the whole code and the account name in the frame, and leave the white border around the code intact, or some phones will not scan it.',
        ],
    ];

    /**
     * Everything the views need, resolved and cached.
     *
     * One query per request at most, and none once it is warm. Cleared
     * whenever anything is saved, so an edit shows immediately.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        /*
         * Only the stored rows are cached, never the addresses built from
         * them.
         *
         * An address built with asset() carries the host it was built on, and
         * whatever warmed the cache decided that host for everybody
         * afterwards. Warmed from the console -- a tinker session, a seeder --
         * it took APP_URL, which is not the port this runs on, and the picture
         * silently failed to load. Warmed through the tunnel used for the
         * survey it would have taken the tunnel's address and then broken for
         * anyone on the machine itself.
         *
         * So the paths are cached and the addresses are made per request, and
         * both are root-relative, which is correct on every host at once.
         */
        $stored = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => SiteSetting::query()->pluck('value', 'key')->all()
        );

        $content = [];

        foreach (self::TEXT_FIELDS as $key => $configKey) {
            $value = $stored[$key] ?? null;

            // An empty string is a value somebody chose: it means "show
            // nothing here". Only a missing row falls back.
            $content[$key] = $value !== null
                ? $value
                : ($configKey ? config($configKey) : null);
        }

        foreach (array_keys(self::IMAGE_FIELDS) as $key) {
            $path = $stored[self::imageKey($key)] ?? null;

            $content[$key . '_url'] = $path && Storage::disk('public')->exists($path)
                ? Storage::url($path)
                : null;

            $content[$key . '_path'] = $path;
            $content[$key . '_uploaded'] = (bool) $content[$key . '_url'];
        }

        /*
         * The GCash QR shipped as a file in public/images long before any of
         * this existed. If nothing has been uploaded, that file is still the
         * right answer -- but it is a fallback rather than something the
         * business chose, so it is not marked as uploaded and the editor does
         * not offer to remove it.
         */
        if (! $content['gcash_qr_url'] && file_exists(public_path('images/gcash-qr.png'))) {
            $content['gcash_qr_url'] = '/images/gcash-qr.png';
        }

        return $content;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);

        self::forget();
    }

    /** The key an uploaded picture's path is stored under. */
    public static function imageKey(string $field): string
    {
        return $field . '_path';
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * The default for a text field, so the editor can show what will be used
     * if the box is left as it is.
     */
    public static function fallback(string $key): ?string
    {
        $configKey = self::TEXT_FIELDS[$key] ?? null;

        return $configKey ? config($configKey) : null;
    }
}
