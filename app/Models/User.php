<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;

    public const ROLE_ADMIN            = 'admin';
    public const ROLE_INVENTORY_STAFF  = 'inventory_staff';
    public const ROLE_ACCOUNTING       = 'accounting';
    public const ROLE_CUSTOMER         = 'customer';

    /** Roles that may reach the back office at all. */
    public const STAFF_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_INVENTORY_STAFF,
        self::ROLE_ACCOUNTING,
    ];

    public const ROLE_LABELS = [
        self::ROLE_ADMIN           => 'Administrator',
        self::ROLE_INVENTORY_STAFF => 'Inventory Staff',
        self::ROLE_ACCOUNTING      => 'Accounting',
        self::ROLE_CUSTOMER        => 'Customer',
    ];

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'email',
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
            'email_verified_at' => 'datetime',
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
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN && $this->is_active;
    }

    public function isInventoryStaff(): bool
    {
        return $this->role === self::ROLE_INVENTORY_STAFF && $this->is_active;
    }

    public function isAccounting(): bool
    {
        return $this->role === self::ROLE_ACCOUNTING && $this->is_active;
    }

    /** Anyone who belongs in the back office, whatever their role. */
    public function isStaff(): bool
    {
        return in_array($this->role, self::STAFF_ROLES, true) && $this->is_active;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER && $this->is_active;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? $this->role;
    }

    /**
     * Where this account belongs after signing in. Staff roles do not all
     * share a landing page, since most of them cannot open the dashboard.
     */
    public function homePath(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN            => '/admin/dashboard',
            self::ROLE_INVENTORY_STAFF  => '/admin/inventory',
            self::ROLE_ACCOUNTING       => '/admin/sales',
            default                     => '/shop',
        };
    }

    // Permissions
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canViewLogs(): bool
    {
        return $this->isAdmin();
    }

    public function canBackupDatabase(): bool
    {
        return $this->isAdmin();
    }

    public function canManageProducts(): bool
    {
        return $this->isAdmin() || $this->isInventoryStaff();
    }

    public function canManageInventory(): bool
    {
        return $this->isAdmin() || $this->isInventoryStaff();
    }

    /** Reading sales history: accounting's whole job, and useful to the warehouse. */
    public function canViewSales(): bool
    {
        return $this->isStaff();
    }

    /** Recording a sale or moving its payment and delivery state. */
    public function canCreateSales(): bool
    {
        return $this->isAdmin();
    }

    public function canViewReports(): bool
    {
        return $this->isAdmin() || $this->isAccounting();
    }

    public function canViewFullDashboard(): bool
    {
        return $this->isAdmin();
    }
}
