<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What customers have said about the products, in one place.
 *
 * One screen for the whole shop rather than a panel inside each product:
 * with forty-five products that would be forty-five screens to check, and
 * the thing the business actually wants to know -- has anybody complained
 * today -- would be spread across all of them.
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|integer',
            'rating'     => 'nullable|integer|between:1,5',
            'only'       => 'nullable|in:all,removed',
            'search'     => 'nullable|string|max:60',
        ]);

        $query = $request->input('only') === 'removed'
            ? ProductReview::onlyTrashed()
            : ProductReview::query();

        $query->with(['product', 'user']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        if ($request->filled('search')) {
            $term = trim($request->input('search'));

            $query->where(function ($q) use ($term) {
                $q->where('body', 'like', '%' . self::escapeLike($term) . '%')
                  ->orWhereHas('product', fn ($p) => $p->where('product_name', 'like', '%' . self::escapeLike($term) . '%'))
                  ->orWhereHas('user', fn ($u) => $u->where('full_name', 'like', '%' . self::escapeLike($term) . '%'));
            });
        }

        $page = $query->orderByDesc('created_at')->orderByDesc('review_id')->paginate($this->perPage());

        return $this->paginated(
            $page,
            fn (ProductReview $review) => [
                'review_id'    => $review->review_id,
                'rating'       => $review->rating,
                'body'         => $review->body,
                'product_name' => $review->product?->product_name ?? 'A product that has been removed',
                'product_id'   => $review->product_id,
                // The business sees the whole name; the shop shows an initial.
                'customer'     => $review->user?->full_name ?? 'A closed account',
                'order_no'     => $review->sale?->order_no,
                'written'      => $review->created_at?->diffForHumans(),
                'removed'      => $review->trashed(),
            ],
            ['summary' => $this->summary()]
        );
    }

    /**
     * Takes a review off the shop.
     *
     * Soft-deleted, not destroyed: a review removed for language was still
     * somebody's experience of a product, and a business that can quietly
     * erase its bad reviews has a review system worth nothing.
     */
    public function destroy(Request $request, int $id)
    {
        $review = ProductReview::with('product')->find($id);

        if (! $review) {
            return response()->json(['success' => false, 'message' => 'That review no longer exists.'], 404);
        }

        $request->validate(
            ['reason' => 'required|string|min:4|max:200'],
            ['reason.required' => 'Say why it is being removed. The reason is kept in the activity log.']
        );

        $review->delete();

        ActivityLog::logAction(
            auth()->id(),
            'review_removed',
            auth()->user()->full_name . ' removed a review of '
                . ($review->product?->product_name ?? 'a deleted product')
                . ': ' . trim($request->input('reason'))
        );

        return response()->json(['success' => true, 'message' => 'Review removed.']);
    }

    /** Puts one back, for a removal that was the wrong call. */
    public function restore(int $id)
    {
        $review = ProductReview::onlyTrashed()->with('product')->find($id);

        if (! $review) {
            return response()->json(['success' => false, 'message' => 'That review is not removed.'], 404);
        }

        $review->restore();

        ActivityLog::logAction(
            auth()->id(),
            'review_restored',
            auth()->user()->full_name . ' put back a review of '
                . ($review->product?->product_name ?? 'a deleted product')
        );

        return response()->json(['success' => true, 'message' => 'Review restored.']);
    }

    /** @return array<string,mixed> */
    private function summary(): array
    {
        $live = ProductReview::query();

        return [
            'total'   => (clone $live)->count(),
            'average' => round((float) (clone $live)->avg('rating'), 1) ?: null,
            'removed' => ProductReview::onlyTrashed()->count(),
            // The shape of opinion at a glance, which a single average hides:
            // ten fives and ten ones average the same as twenty threes.
            'spread'  => DB::table('product_reviews')
                ->whereNull('deleted_at')
                ->selectRaw('rating, COUNT(*) AS count')
                ->groupBy('rating')
                ->orderByDesc('rating')
                ->pluck('count', 'rating'),
        ];
    }

    /** Wildcards are literal characters in a customer's words. */
    private static function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }
}
