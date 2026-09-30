<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One editable piece of the storefront, stored as a key and a value.
 *
 * Read through App\Support\SiteContent rather than directly, so that every
 * caller gets the same fallback to configuration and the same cache.
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];
}
