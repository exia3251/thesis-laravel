<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\ReviewMedia;
use Illuminate\Http\Request;

/**
 * Reading and writing the reviews on a product.
 *
 * Reading is open to anybody, signed in or not: a review a customer cannot
 * see before buying is a review that does them no good. Writing is not.
 */
class ProductReviewController extends Controller
{
    /** Every review on this product, newest first, with the summary. */
    public function index(Request $request, int $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'That product no longer exists.'], 404);
        }

        $page = ProductReview::with(['user', 'media'])
            ->where('product_id', $productId)
            ->orderByDesc('created_at')
            ->paginate($this->perPage(10));

        return $this->paginated(
            $page,
            fn (ProductReview $review) => [
                'review_id' => $review->review_id,
                'rating'    => $review->rating,
                'body'      => $review->body,
                'author'    => $review->displayName(),
                // Every review here came from a delivered order, which is the
                // whole basis on which it was accepted, so it is worth saying.
                'verified'  => true,
                'written'   => $review->created_at?->diffForHumans(),
                'media'     => $review->media->map(fn ($m) => [
                    'kind' => $m->kind,
                    'url'  => $m->url(),
                    'type' => $m->mime_type,
                ])->values(),
            ],
            [
                'summary' => [
                    'average' => $product->averageRating(),
                    'count'   => $product->reviewCount(),
                    // What this particular visitor may do, so the page does
                    // not have to work it out and get it wrong.
                    'can_review' => ProductReview::canReview($request->user(), $productId),
                ],
            ]
        );
    }

    public function store(Request $request, int $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'That product no longer exists.'], 404);
        }

        /*
         * Checked before the input is looked at, so somebody who may not
         * review is told that, rather than being walked through validation
         * errors for a form that was never going to be accepted.
         */
        $sale = ProductReview::eligibleSale($request->user(), $productId);

        if (! $sale) {
            return response()->json([
                'success' => false,
                'message' => 'Reviews come from customers who have received this item. '
                    . 'Once your order arrives you can leave one.',
            ], 403);
        }

        if (! ProductReview::canReview($request->user(), $productId)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this product.',
            ], 409);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:' . ProductReview::MIN_RATING . ',' . ProductReview::MAX_RATING],
            'body'   => ['nullable', 'string', 'max:1000'],
        ] + ReviewMedia::rules(), [
            'rating.required' => 'Choose a rating from one to five stars.',
            'rating.between'  => 'A rating runs from one star to five.',
            'body.max'        => 'Keep a review under 1,000 characters.',
        ] + ReviewMedia::messages());

        /*
         * The per-type ceiling, which the shared rule cannot express: a
         * photograph is held to four megabytes and a clip to twenty, and one
         * rule covering both would have to allow twenty for either.
         *
         * Checked before anything is written, so a review is never saved with
         * half its attachments.
         */
        foreach ($request->file('media', []) as $index => $file) {
            if ($problem = ReviewMedia::sizeProblem($file)) {
                return response()->json([
                    'success' => false,
                    'message' => $problem,
                    'errors'  => ['media.' . $index => [$problem]],
                ], 422);
            }
        }

        $review = ProductReview::create([
            'product_id' => $productId,
            'user_id'    => $request->user()->user_id,
            // Recorded, so the claim that this was a real purchase can be
            // checked later against the order itself.
            'sale_id'    => $sale->sale_id,
            'rating'     => $validated['rating'],
            'body'       => blank($validated['body'] ?? null) ? null : trim($validated['body']),
        ]);

        foreach ($request->file('media', []) as $file) {
            ReviewMedia::attach($review, $file);
        }

        ActivityLog::logAction(
            $request->user()->user_id,
            'product_reviewed',
            "{$request->user()->full_name} reviewed {$product->product_name} ({$review->rating} of 5)"
        );

        return response()->json([
            'success' => true,
            'message' => 'Thank you. Your review is on the product page.',
        ]);
    }
}
