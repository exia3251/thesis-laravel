<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * One place on the Philippine Statistics Authority's list.
 *
 * A province, a city or municipality, or a barangay, each knowing the code of
 * the place it sits inside. Reference data: seeded, read-only, and the same on
 * every install.
 */
class PsgcLocation extends Model
{
    public const PROVINCE = 'province';
    public const CITY     = 'city';
    public const BARANGAY = 'barangay';

    protected $table = 'psgc_locations';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['code', 'name', 'level', 'parent_code'];

    public function scopeProvinces(Builder $query): Builder
    {
        return $query->where('level', self::PROVINCE);
    }

    /** The places directly inside one, in the order a list should show them. */
    public function scopeInside(Builder $query, string $parentCode): Builder
    {
        return $query->where('parent_code', $parentCode)->orderBy('name');
    }

    /**
     * What a dropdown needs and nothing else.
     *
     * The whole child list travels at once rather than a request per
     * keystroke: the largest of them is one city's barangays, and sending it
     * whole is both smaller than the requests it saves and instant to search
     * once it is there.
     */
    public static function options(Builder $query): Collection
    {
        return $query->get(['code', 'name'])
            ->map(fn (self $row) => ['code' => $row->code, 'name' => $row->name])
            ->values();
    }

    /**
     * Finds a place by the name somebody typed or stored.
     *
     * Needed because addresses already in the database were free text, and
     * because the PSA writes "City of Imus" where a person writes "Imus" or
     * "Imus City". Matching is case-insensitive and ignores the ways a city
     * name gets rearranged, so an address saved before these lists existed
     * still resolves to the row it meant.
     */
    public static function findNamed(string $name, string $level, ?string $parentCode = null): ?self
    {
        $query = static::where('level', $level);

        if ($parentCode !== null) {
            $query->where('parent_code', $parentCode);
        }

        $wanted = self::normalise($name);

        if ($wanted === '') {
            return null;
        }

        return $query->get()->first(fn (self $row) => self::normalise($row->name) === $wanted);
    }

    /**
     * Strips a name down to the part that identifies it.
     *
     * "City of Imus", "Imus City" and "imus" all reduce to "imus"; the
     * bracketed "(Pob.)" the PSA appends to a poblacion barangay goes too, and
     * so does punctuation, which is written inconsistently everywhere.
     */
    public static function normalise(string $name): string
    {
        $value = mb_strtolower(trim($name));

        $value = preg_replace('/\s*\([^)]*\)\s*/', ' ', $value);
        $value = preg_replace('/^city of\s+/', '', $value);
        $value = preg_replace('/\s+city$/', '', $value);
        $value = preg_replace('/^(brgy|bgy|barangay)\.?\s+/', '', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value);

        return $value;
    }
}
