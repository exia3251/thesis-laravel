<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $primaryKey = 'sale_id';
    public $timestamps = false;
    
    protected $fillable = [
        'customer_name', 'user_id', 'total_amount',
        'payment_method', 'status', 'sale_date'
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function items() {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    // Calculate total from items
    public function calculateTotal() {
        return $this->items()->sum('subtotal');
    }
}