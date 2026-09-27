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
        'receipt_no',
        'delivery_no',
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
        'refund_reason',
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
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
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
        return $this->belongsTo(User::class, 'cancelled_by', 'user_id')->withTrashed();
    }

    public function refunder()
    {
        return $this->belongsTo(User::class, 'refunded_by', 'user_id')->withTrashed();
    }

    public function deliveryConfirmer()
    {
        return $this->belongsTo(User::class, 'delivery_confirmed_by', 'user_id')->withTrashed();
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
        $percent = (float) config('payments.minimum_down_payment_percent', 50);

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

    /**
     * What a customer quotes back over the phone. Falls back to the row id
     * only for orders placed before order numbers existed.
     */
    public function reference(): string
    {
        return $this->order_no ?: ('#' . $this->sale_id);
    }

    /**
     * The tax lines a document has to show.
     *
     * Catalogue prices are tax inclusive, which is how retail prices are
     * quoted here, so VAT is worked back out of the total rather than added
     * to it. A seller below the threshold shows no VAT at all and says so
     * instead, which is a statement the document is required to make either
     * way.
     *
     * @return array{registered: bool, subtotal: float, vatable: float, vat: float, total: float, rate: float}
     */
    public function taxBreakdown(): array
    {
        $total = (float) $this->total_amount;
        $registered = (bool) config('business.vat_registered');
        $rate = (float) config('business.vat_rate', 12);

        if (!$registered || $rate <= 0) {
            return [
                'registered' => false,
                'subtotal' => $total,
                'vatable' => 0.0,
                'vat' => 0.0,
                'total' => $total,
                'rate' => $rate,
            ];
        }

        $vatable = $total / (1 + ($rate / 100));

        return [
            'registered' => true,
            'subtotal' => round($vatable, 2),
            'vatable' => round($vatable, 2),
            'vat' => round($total - $vatable, 2),
            'total' => $total,
            'rate' => $rate,
        ];
    }

    /**
     * Every movement of money on this order, in the order it happened.
     *
     * The receipt used to show a summary beside a list of payment requests
     * and leave the customer to reconcile the two, which is hardest on
     * exactly the split-payment orders this shop encourages. Staff can also
     * record cash that never passed through a request, so anything paid but
     * unaccounted for is shown rather than quietly folded into a total.
     *
     * @return array<int,array{label: string, detail: ?string, amount: float, state: string, date: ?\Illuminate\Support\Carbon}>
     */
    public function paymentTimeline(): array
    {
        $rows = [[
            'label' => 'Order placed',
            'detail' => $this->planLabel(),
            'amount' => (float) $this->total_amount,
            'state' => 'neutral',
            'date' => $this->sale_date,
        ]];

        $accountedFor = 0.0;

        foreach ($this->paymentRequests->sortBy('created_at') as $request) {
            $approved = $request->status === 'approved';

            if ($approved) {
                $accountedFor += (float) $request->amount;
            }

            $rows[] = [
                // ucwords would render the brand as "Gcash".
                'label' => (strcasecmp($request->payment_method, 'gcash') === 0
                    ? 'GCash'
                    : ucwords(str_replace('_', ' ', $request->payment_method))) . ' payment',
                'detail' => $request->reference_no ? 'Ref ' . $request->reference_no : null,
                'amount' => (float) $request->amount,
                'state' => match ($request->status) {
                    'approved' => 'in',
                    'rejected' => 'void',
                    default => 'pending',
                },
                'date' => $request->created_at,
            ];
        }

        // Cash taken on delivery, or an amount an administrator entered by
        // hand, never has a request behind it.
        $unexplained = round((float) $this->paid_amount - $accountedFor, 2);

        if ($unexplained > 0.009) {
            $rows[] = [
                'label' => 'Payment recorded by staff',
                'detail' => 'Cash or settled directly',
                'amount' => $unexplained,
                'state' => 'in',
                'date' => $this->received_at ?? $this->updated_at,
            ];
        }

        // Money going back is still a movement of money. Without this a
        // cancelled order showed what the customer paid and never showed it
        // returned.
        if ($this->refund_status === self::REFUND_PENDING) {
            $rows[] = [
                'label' => 'Refund owed to you',
                'detail' => null,
                'amount' => (float) $this->refund_amount,
                'state' => 'due',
                'date' => $this->cancelled_at,
            ];
        } elseif ($this->refund_status === self::REFUND_DONE) {
            $rows[] = [
                'label' => 'Refunded',
                'detail' => $this->refund_reference ? 'Ref ' . $this->refund_reference : null,
                'amount' => (float) $this->refund_amount,
                'state' => 'out',
                'date' => $this->refunded_at,
            ];
        }

        if ((float) $this->balance_due > 0 && !$this->isCancelled()) {
            $rows[] = [
                'label' => $this->payment_plan === self::PLAN_COD || $this->codAmount() > 0
                    ? 'Balance due on delivery'
                    : 'Balance outstanding',
                'detail' => null,
                'amount' => (float) $this->balance_due,
                'state' => 'due',
                'date' => null,
            ];
        }

        return $rows;
    }
}
