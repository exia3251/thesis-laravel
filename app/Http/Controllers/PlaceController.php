<?php

namespace App\Http\Controllers;

use App\Models\PsgcLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * The lists behind the three address dropdowns.
 *
 * Reachable without signing in, because the first place they are needed is
 * the registration form. Nothing here is anybody's data -- it is the
 * Philippine Statistics Authority's list of place names, the same for every
 * visitor -- so there is nothing to protect and everything to cache.
 *
 * A whole child list is sent at once rather than a request per keystroke. The
 * largest of them is one city's barangays, which is smaller than the requests
 * it saves, and searching a list that is already in the browser is instant.
 */
class PlaceController extends Controller
{
    /** Reference data. It changes when the PSA publishes, not while we run. */
    private const CACHE_HOURS = 24;

    public function provinces()
    {
        return response()->json([
            'success' => true,
            'data' => Cache::remember(
                'places.provinces',
                now()->addHours(self::CACHE_HOURS),
                fn () => PsgcLocation::options(PsgcLocation::query()->provinces()->orderBy('name'))
            ),
        ]);
    }

    /**
     * Turns three saved names into the codes the lists are keyed by.
     *
     * A page that renders its form from the server resolves these while it
     * renders. The back office's user panel cannot -- its form is one dialog
     * reused for whichever row was clicked -- so it asks here instead, once
     * per row opened rather than once per row listed.
     *
     * Matching is forgiving, because these names were free text until now:
     * "Imus", "Imus City" and the PSA's "City of Imus" all find the same row.
     * A name that matches nothing comes back null rather than as an error --
     * the panel still shows it, so an address the lists disagree with is
     * visible to whoever has to correct it.
     */
    public function resolve(Request $request)
    {
        $wanted = $request->validate([
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'barangay' => ['nullable', 'string', 'max:100'],
        ]);

        $province = filled($wanted['province'] ?? null)
            ? PsgcLocation::findNamed($wanted['province'], PsgcLocation::PROVINCE)
            : null;

        $city = ($province && filled($wanted['city'] ?? null))
            ? PsgcLocation::findNamed($wanted['city'], PsgcLocation::CITY, $province->code)
            : null;

        $barangay = ($city && filled($wanted['barangay'] ?? null))
            ? PsgcLocation::findNamed($wanted['barangay'], PsgcLocation::BARANGAY, $city->code)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'province' => $province ? ['code' => $province->code, 'name' => $province->name] : null,
                'city' => $city ? ['code' => $city->code, 'name' => $city->name] : null,
                'barangay' => $barangay ? ['code' => $barangay->code, 'name' => $barangay->name] : null,
            ],
        ]);
    }

    /**
     * Everything directly inside one place: a province's cities, or a city's
     * barangays. One route for both, because the question is the same.
     */
    public function children(string $code)
    {
        // Codes are nine digits and nothing else, so a malformed one is
        // turned away before it reaches the database.
        if (! preg_match('/^\d{9}$/', $code)) {
            return response()->json(['success' => false, 'message' => 'Not a place code.'], 422);
        }

        return response()->json([
            'success' => true,
            'data' => Cache::remember(
                "places.children.{$code}",
                now()->addHours(self::CACHE_HOURS),
                fn () => PsgcLocation::options(PsgcLocation::query()->inside($code))
            ),
        ]);
    }
}
