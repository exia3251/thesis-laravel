<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShoppingCart extends Model
{
    protected $table = 'shopping_cart';
    protected $primaryKey = 'cart_id';
    public $timestamps = false;
    
    protected $fillable = ['user_id', 'product_id', 'quantity', 'added_at'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    // Get cart total for user
    public static function getCartTotal($userId) {
        return self::where('user_id', $userId)
            ->join('products', 'shopping_cart.product_id', '=', 'products.product_id')
            ->selectRaw('SUM(shopping_cart.quantity * products.price) as total')
            ->value('total') ?? 0;
    }
}