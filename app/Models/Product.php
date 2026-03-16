<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';
    
    protected $fillable = [
        'product_name',
        'brand',
        'oil_type',
        'viscosity_grade',
        'unit',
        'price',
        'reorder_level',
        'description'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // Relationships
    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id', 'product_id');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'product_id', 'product_id');
    }

    public function cartItems()
    {
        return $this->hasMany(ShoppingCart::class, 'product_id', 'product_id');
    }

    // Helper methods
    public function isLowStock()
    {
        return $this->inventory && $this->inventory->quantity <= $this->reorder_level;
    }

    public function isOutOfStock()
    {
        return $this->inventory && $this->inventory->quantity == 0;
    }

    public function getStockStatus()
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }
}
