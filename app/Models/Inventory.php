<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    protected $primaryKey = 'inventory_id';
    
    public $timestamps = false;
    
    protected $fillable = ['product_id', 'quantity', 'last_updated'];

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // Update stock
    public static function updateStock($productId, $quantity) {
        $inventory = self::firstOrCreate(
            ['product_id' => $productId],
            ['quantity' => 0]
        );
        
        $inventory->quantity += $quantity;
        $inventory->last_updated = now();
        $inventory->save();
        
        return $inventory;
    }
}