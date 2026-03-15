<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    
    protected $fillable = ['username', 'password', 'full_name', 'role'];
    
    protected $hidden = ['password'];

    /**
     * Auto-hash passwords when setting
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'user_id', 'user_id');
    }

    public function cart()
    {
        return $this->hasMany(ShoppingCart::class, 'user_id', 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'user_id', 'user_id');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'user_id', 'user_id');
    }

    public function stockTransactions()
    {
        return $this->hasMany(StockTransaction::class, 'user_id', 'user_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return in_array($this->role, ['admin', 'staff']);
    }

    public function isCustomer()
    {
        return $this->role === 'customer';
    }
}
