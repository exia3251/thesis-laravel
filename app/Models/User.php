<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    
    protected $fillable = [
        'username', 
        'password', 
        'full_name', 
        'role',
        'is_active',
        'current_session_id',
    ];
    
    protected $hidden = ['password', 'current_session_id'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
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

    // Role checks
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin' && $this->is_active;
    }

    public function isAdmin()
    {
        return in_array($this->role, ['super_admin', 'admin']) && $this->is_active;
    }

    public function isCustomer()
    {
        return $this->role === 'customer' && $this->is_active;
    }
    
    // Permissions
    public function canManageUsers()
    {
        return $this->isSuperAdmin();
    }

    public function canManageProducts()
    {
        return $this->isAdmin();
    }

    public function canManageInventory()
    {
        return $this->isAdmin();
    }

    public function canCreateSales()
    {
        return $this->isAdmin();
    }

    public function canViewReports()
    {
        return $this->isAdmin();
    }
}
