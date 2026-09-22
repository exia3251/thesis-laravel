<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StockTransaction extends Model
{
    protected $primaryKey = 'transaction_id';
    protected $fillable = ['product_id', 'transaction_type', 'quantity', 'reference_number', 'notes', 'user_id', 'transaction_date'];
    protected $casts = ['transaction_date' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    public static function logTransaction(
        int $productId,
        string $transactionType,
        int $quantity,
        ?string $referenceNumber = null,
        ?string $notes = null,
        ?int $userId = null
    ): self {
        $normalizedType = match (strtolower($transactionType)) {
            'in', 'stock_in' => 'stock_in',
            'out', 'sale', 'stock_out' => 'stock_out',
            default => 'adjustment',
        };

        return static::create([
            'product_id' => $productId,
            'transaction_type' => $normalizedType,
            'quantity' => $quantity,
            'reference_number' => $referenceNumber,
            'notes' => $notes,
            'user_id' => $userId ?? Auth::id(),
            'transaction_date' => now(),
        ]);
    }
}
