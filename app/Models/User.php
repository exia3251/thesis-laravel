<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;

    /**
     * Required by MustVerifyEmail: sendEmailVerificationNotification() calls
     * notify(), which lives here. Without it that call raised "undefined
     * method notify()", and since both registration and the resend button
     * catch and log the failure, no verification email was ever sent while
     * the interface was declared. Nothing looked broken from the outside.
     */
    use Notifiable;

    /**
     * Deleting an account archives it, keeping the audit trail and every
     * order it placed readable. An archived account cannot sign in, because
     * the scope hides it from the query the auth guard runs.
     */
    use SoftDeletes;

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
        'google_id',
        'password',
        'full_name',
        'avatar_path',
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

    /**
     * How many bottles are waiting in this account's cart.
     *
     * Counted as items rather than lines, because that is the number a
     * customer is keeping track of: four bottles of one oil reads as four,
     * the way the cart page totals them.
     */
    public function cartItemCount(): int
    {
        return (int) $this->cart()->sum('quantity');
    }

    /**
     * Orders that are waiting on the customer for something -- money owed, or
     * goods with the courier that need confirming. Shown as a count beside
     * Orders, so a customer does not have to open the page to find out whether
     * anything needs them.
     */
    public function ordersNeedingAttention(): int
    {
        return (int) $this->sales()
            ->where('order_status', '!=', Sale::STATUS_CANCELLED)
            ->where(function ($query) {
                $query->where('payment_status', '!=', 'paid')
                    ->orWhere(function ($awaiting) {
                        $awaiting->where('delivery_status', 'to_receive')
                            ->whereNull('received_at');
                    });
            })
            ->count();
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

    /**
     * Whether this customer still has to say where an order should go.
     *
     * An account made through Google Sign-In arrives with a name and an
     * address for email and nothing else: nobody asked it for a phone number
     * or somewhere to deliver to. Checkout refuses without them, so it is
     * worth saying so long before anyone reaches checkout.
     */
    public function needsDeliveryDetails(): bool
    {
        if (!$this->isCustomer()) {
            return false;
        }

        $profile = $this->customerProfile;

        return !$profile || !$profile->isComplete();
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/' . $this->avatar_path) : null;
    }

    /**
     * Fallback when no photo has been uploaded: the first letter of each of
     * the first two words, so "Maria Santos" reads MS.
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->full_name)) ?: [];
        $letters = array_map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)), array_slice($words, 0, 2));

        return implode('', $letters) ?: '?';
    }

    /** A stable colour per account, so the same person is the same colour. */
    public function avatarTone(): string
    {
        $tones = [
            'bg-emerald-100 text-emerald-800',
            'bg-sky-100 text-sky-800',
            'bg-amber-100 text-amber-800',
            'bg-violet-100 text-violet-800',
            'bg-rose-100 text-rose-800',
            'bg-teal-100 text-teal-800',
        ];

        return $tones[$this->user_id % count($tones)];
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

    /** Editing what the storefront assistant says on the company's behalf. */
    public function canManageChatbot(): bool
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
