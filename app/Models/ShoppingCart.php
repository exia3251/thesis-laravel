<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    protected $table = 'shopping_cart';
    protected $primaryKey = 'cart_id';
    protected $fillable = ['user_id', 'product_id', 'quantity'];
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function product() { return $this->belongsTo(Product::class, 'product_id', 'product_id'); }
}