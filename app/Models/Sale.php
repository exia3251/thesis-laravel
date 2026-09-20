<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    public const PLAN_COD        = 'cod';
    public const PLAN_GCASH_FULL = 'gcash_full';
    public const PLAN_SPLIT      = 'split';

    public const PLAN_LABELS = [
        self::PLAN_COD        => 'Cash on Delivery',
        self::PLAN_GCASH_FULL => 'GCash (full payment)',
        self::PLAN_SPLIT      => 'Split - GCash down payment, balance on delivery',
    ];

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';

    public const REFUND_NONE     = 'none';
    public const REFUND_PENDING  = 'pending';
    public const REFUND_DONE     = 'refunded';

    protected $primaryKey = 'sale_id';
    protected $fillable = [
        'user_id',
        'customer_name',
        'delivery_address',
        'contact_phone',
        'total_amount',
        'payment_method',
        'payment_plan',
        'gcash_amount',
        'payment_status',
        'paid_amount',
        'balance_due',
        'delivery_status',
        'order_status',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'refund_status',
        'refund_amount',
        'refund_reference',
        'refund_notes',
        'refunded_at',
        'refunded_by',
        'delivery_proof_path',
        'delivered_at',
        'delivery_confirmed_by',
        'received_at',
        'sale_date',
    ];
    protected $casts = [
        'total_amount' => 'decimal:2',
        'gcash_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'sale_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'sale_id', 'sale_id')->latest();
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by', 'user_id');
    }

    public function refunder()
    {
        return $this->belongsTo(User::class, 'refunded_by', 'user_id');
    }

    public function deliveryConfirmer()
    {
        return $this->belongsTo(User::class, 'delivery_confirmed_by', 'user_id');
    }

    public function planLabel(): string
    {
        return self::PLAN_LABELS[$this->payment_plan] ?? $this->payment_plan;
    }

    /**
     * The share of this order the customer committed to settling in cash when
     * it arrives, as opposed to online beforehand.
     */
    public function codAmount(): float
    {
        return max((float) $this->total_amount - (float) $this->gcash_amount, 0);
    }

    /**
     * How much of the online commitment is still outstanding. Drives whether
     * the customer is still shown the payment form.
     */
    public function gcashOutstanding(): float
    {
        return max((float) $this->gcash_amount - (float) $this->paid_amount, 0);
    }

    /** The smallest GCash down payment this order may be split with. */
    public static function minimumDownPayment(float $total): float
    {
        $percent = (float) config('payments.minimum_down_payment_percent', 20);

        return round($total * $percent / 100, 2);
    }

    public function isCancelled(): bool
    {
        return $this->order_status === self::STATUS_CANCELLED;
    }

    public function isDelivered(): bool
    {
        return $this->delivery_status === 'delivered';
    }

    /**
     * An order can be called off until it has been handed over. After that it
     * would be a return, which is a different process and out of scope here.
     */
    public function canBeCancelled(): bool
    {
        return !$this->isCancelled() && !$this->isDelivered();
    }

    /**
     * A customer may not cancel while a payment of theirs is still being
     * reviewed, or the refund owed would move underneath the decision.
     */
    public function canBeCancelledByCustomer(): bool
    {
        return $this->canBeCancelled()
            && !$this->paymentRequests()->where('status', 'processing')->exists();
    }

    public function owesRefund(): bool
    {
        return $this->refund_status === self::REFUND_PENDING;
    }

    public function statusLabel(): string
    {
        if ($this->isCancelled()) {
            return match ($this->refund_status) {
                self::REFUND_PENDING => 'Cancelled - refund owed',
                self::REFUND_DONE    => 'Cancelled - refunded',
                default              => 'Cancelled',
            };
        }

        return $this->isDelivered() ? 'Delivered' : 'In progress';
    }
}
