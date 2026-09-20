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
        'sale_date',
    ];
    protected $casts = [
        'total_amount' => 'decimal:2',
        'gcash_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'sale_date' => 'datetime',
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
}
