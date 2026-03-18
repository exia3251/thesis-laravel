<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $primaryKey = 'transaction_id';
    protected $fillable = ['product_id', 'transaction_type', 'quantity', 'reference_number', 'notes', 'user_id', 'transaction_date'];
    protected $casts = ['transaction_date' => 'datetime'];
    public function product() { return $this->belongsTo(Product::class, 'product_id', 'product_id'); }
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
}