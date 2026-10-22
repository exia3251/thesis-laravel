<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One customer's verdict on one oil they bought.
 *
 * Whether somebody may write one is decided here, in eligibleSale(), so the
 * form, the request that submits it and the test all ask the same question
 * of the same code. It was the three-places-and-they-drift problem that put
 * the order rules in Sale, and this is the same shape of rule.
 */
class ProductReview extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'review_id';

    protected $fillable = ['product_id', 'user_id', 'sale_id', 'rating', 'body'];

    protected $casts = ['rating' => 'integer'];

    public const MIN_RATING = 1;
    public const MAX_RATING = 5;

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id', 'sale_id');
    }

    /** Photographs and clips the customer attached. */
    public function media()
    {
        return $this->hasMany(ProductReviewMedia::class, 'review_id', 'review_id');
    }

    /**
     * The delivered order that entitles this customer to review this product,
     * or null if there is none.
     *
     * Delivered, not merely paid: the question a review answers is what the
     * oil was like, which nobody can say until it has arrived. An order still
     * with the courier does not count, and neither does a cancelled one.
     */
    public static function eligibleSale(?User $user, int $productId): ?Sale
    {
        if (! $user || ! $user->isCustomer()) {
            return null;
        }

        return Sale::query()
            ->where('user_id', $user->user_id)
            ->where('delivery_status', 'delivered')
            ->where('order_status', '<>', Sale::STATUS_CANCELLED)
            ->whereHas('items', fn ($q) => $q->where('product_id', $productId))
            ->orderBy('sale_id')
            ->first();
    }

    /** Whether this customer still has a review to write for this product. */
    public static function canReview(?User $user, int $productId): bool
    {
        if (! self::eligibleSale($user, $productId)) {
            return false;
        }

        return ! self::withTrashed()
            ->where('user_id', $user->user_id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * The name to show against a review.
     *
     * A surname is more than a stranger needs in order to read an opinion
     * about engine oil, so it is cut to an initial. The full name stays on
     * the account, where the business can see it.
     */
    public function displayName(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->user?->full_name)) ?: [];
        $first = $parts[0] ?? 'Customer';
        $last  = count($parts) > 1 ? ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : '';

        return $first . $last;
    }
}
