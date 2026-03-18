<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $primaryKey = 'sale_id';
    protected $fillable = ['user_id', 'total_amount', 'sale_date'];
    protected $casts = ['total_amount' => 'decimal:2', 'sale_date' => 'datetime'];
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
    public function items() { return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id'); }
}