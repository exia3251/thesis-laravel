<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;
    
    protected $fillable = [
        'product_id', 'transaction_type', 'quantity',
        'reference_no', 'notes', 'user_id', 'transaction_date'
    ];

    protected $casts = [
        'transaction_date' => 'datetime'
    ];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Log transaction
    public static function logTransaction($productId, $type, $quantity, $reference = null, $notes = null) {
        return self::create([
            'product_id' => $productId,
            'transaction_type' => $type,
            'quantity' => $quantity,
            'reference_no' => $reference,
            'notes' => $notes,
            'user_id' => auth()->id(),
            'transaction_date' => now()
        ]);
    }
}