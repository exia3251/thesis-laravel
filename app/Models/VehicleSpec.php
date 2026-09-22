<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One engine's oil requirement. See the migration for why is_verified and
 * source exist.
 */
class VehicleSpec extends Model
{
    protected $primaryKey = 'spec_id';

    protected $fillable = [
        'make', 'model', 'variant', 'fuel',
        'year_from', 'year_to',
        'viscosity', 'viscosity_alt', 'oil_type',
        'capacity_litres', 'aliases', 'notes', 'source', 'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'capacity_litres' => 'decimal:1',
    ];

    /** "Toyota Hilux 2.8 Diesel" */
    public function title(): string
    {
        return trim($this->make . ' ' . $this->model . ' ' . (string) $this->variant);
    }

    /** "2016 onwards", "2005 to 2015", or "" when it applies to all years. */
    public function yearLabel(): string
    {
        if (!$this->year_from && !$this->year_to) {
            return '';
        }

        if ($this->year_from && !$this->year_to) {
            return $this->year_from . ' onwards';
        }

        if (!$this->year_from && $this->year_to) {
            return 'up to ' . $this->year_to;
        }

        return $this->year_from . ' to ' . $this->year_to;
    }

    public function coversYear(?int $year): bool
    {
        if ($year === null) {
            return true;
        }

        return ($this->year_from === null || $year >= $this->year_from)
            && ($this->year_to === null || $year <= $this->year_to);
    }

    /** Every spelling this row should be found by. */
    public function searchNames(): array
    {
        $names = [mb_strtolower($this->model)];

        foreach (preg_split('/\s*,\s*/', (string) $this->aliases) ?: [] as $alias) {
            if (trim($alias) !== '') {
                $names[] = mb_strtolower(trim($alias));
            }
        }

        return array_values(array_unique($names));
    }
}
