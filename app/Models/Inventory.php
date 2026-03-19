<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Inventory extends Model
{
    protected $table = 'inventory';
    protected $primaryKey = 'inventory_id';
    protected $fillable = ['product_id', 'quantity', 'last_updated'];
    protected $casts = ['last_updated' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public static function updateStock(int $productId, int $quantityChange): self
    {
        $inventory = static::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0]
        );

        $newQuantity = $inventory->quantity + $quantityChange;

        if ($newQuantity < 0) {
            throw new RuntimeException('Stock cannot go below zero.');
        }

        $inventory->quantity = $newQuantity;
        $inventory->last_updated = now();
        $inventory->save();

        return $inventory;
    }
}
