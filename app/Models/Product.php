<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $primaryKey = 'product_id';
    
    protected $fillable = [
        'product_name', 'brand', 'oil_type', 'viscosity_grade',
        'unit', 'price', 'reorder_level', 'supplier_id', 'description'
    ];

    public function supplier() {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function inventory() {
        return $this->hasOne(Inventory::class, 'product_id', 'product_id');
    }

    public function stockTransactions() {
        return $this->hasMany(StockTransaction::class, 'product_id', 'product_id');
    }

    public function saleItems() {
        return $this->hasMany(SaleItem::class, 'product_id', 'product_id');
    }

    // Get current stock
    public function getCurrentStock() {
        return $this->inventory ? $this->inventory->quantity : 0;
    }

    // Check if low stock
    public function isLowStock() {
        return $this->getCurrentStock() <= $this->reorder_level;
    }
}