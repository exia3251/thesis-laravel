<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    protected $primaryKey = 'profile_id';

    protected $fillable = [
        'user_id', 'phone',
        'house_street', 'barangay', 'city', 'province', 'postal_code',
    ];

    /** Sent with every profile read, so the view never composes it itself. */
    protected $appends = ['full_address', 'is_complete'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    /**
     * The address on one line, which is what goes onto an order and a
     * receipt. Composed rather than stored, so it cannot fall out of step
     * with the fields it is built from.
     */
    public function fullAddress(): string
    {
        return collect([
            $this->house_street,
            $this->barangay ? 'Brgy. ' . $this->barangay : null,
            $this->city,
            $this->province,
            $this->postal_code,
        ])->filter(fn ($part) => filled(trim((string) $part)))->implode(', ');
    }

    public function getFullAddressAttribute(): string
    {
        return $this->fullAddress();
    }

    /**
     * Whether this is enough to deliver to. The postal code is left out on
     * purpose: couriers here manage without it, and demanding it would block
     * checkout for people who simply do not know theirs.
     */
    public function isComplete(): bool
    {
        foreach (['house_street', 'barangay', 'city', 'province'] as $field) {
            if (blank(trim((string) $this->{$field}))) {
                return false;
            }
        }

        return filled(trim((string) $this->phone));
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->isComplete();
    }
}
