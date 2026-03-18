<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    protected $primaryKey = 'inventory_id';
    protected $fillable = ['product_id', 'quantity', 'last_updated'];
    protected $casts = ['last_updated' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class, 'product_id', 'product_id'); }
}