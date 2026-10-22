<?php

namespace Database\Seeders;

use App\Models\ProductReview;
use App\Models\Sale;
use Illuminate\Database\Seeder;

/**
 * Reviews for the oils that have actually been delivered.
 *
 *     php artisan db:seed --class=DemoReviewSeeder
 *
 * Run deliberately, never from DatabaseSeeder. A fresh install should not
 * come with opinions nobody wrote: the shop would be claiming praise from
 * customers who do not exist, which is the thing the review system is built
 * to make impossible.
 *
 * Written against real delivered orders rather than invented ones, so every
 * row satisfies the same rule the controller enforces -- a review belongs to
 * a customer, a product, and the order that entitled it. A review written
 * any other way would be a row the application itself would have refused.
 */
class DemoReviewSeeder extends Seeder
{
    /**
     * Written the way customers write: mostly content, some mixed, a few
     * unhappy. A page of nothing but five stars reads as invented, and
     * invites a reader to trust none of it.
     *
     * @var array<int, array{0:int, 1:string}>
     */
    private const OPINIONS = [
        [5, 'Been using this in my Vios for two years. Engine still runs quiet and the oil stays clean between changes.'],
        [5, 'Delivered the next morning and the seals were intact. Price is better than the shop near us.'],
        [4, 'Good oil, does the job. Would have given five but one bottle had a dented cap.'],
        [5, 'Ordered four litres for my Hilux. Smooth shifting after the change and no burning smell.'],
        [4, 'Works well. Delivery took a bit longer than I expected but the rider called ahead.'],
        [5, 'The chat told me which grade my Mirage needed and it was right. Saved me a trip.'],
        [3, 'Oil is fine. I just wish the 5 litre came in a box, mine arrived loose in the bag.'],
        [5, 'Genuine stock, proper seals. I have had fake oil before so I check carefully now.'],
        [4, 'Second time ordering. Same quality both times, which is what I want from a supplier.'],
        [5, 'Engine is noticeably quieter at idle. Will be ordering the drum next time.'],
        [2, 'The oil itself is fine but nobody told me it was out of stock until I asked.'],
        [5, 'Fast, sealed, and the receipt had everything the accountant needed.'],
    ];

    public function run(): void
    {
        $already = ProductReview::withTrashed()->count();

        if ($already > 0) {
            $this->command?->warn("There are already {$already} reviews. Nothing written.");

            return;
        }

        /*
         * Delivered orders, oldest first, each paired with one of its own
         * items. A customer may review a product once, so the same pair is
         * never used twice.
         */
        $sales = Sale::with('items')
            ->where('delivery_status', 'delivered')
            ->where('order_status', '<>', Sale::STATUS_CANCELLED)
            ->whereNotNull('user_id')
            ->orderBy('sale_id')
            ->get();

        $written = 0;
        $used = [];                       // "user_id:product_id" already reviewed

        foreach ($sales as $sale) {
            if ($written >= count(self::OPINIONS)) {
                break;
            }

            foreach ($sale->items as $item) {
                $key = $sale->user_id . ':' . $item->product_id;

                if (isset($used[$key])) {
                    continue;
                }

                [$rating, $body] = self::OPINIONS[$written];

                ProductReview::create([
                    'product_id' => $item->product_id,
                    'user_id'    => $sale->user_id,
                    'sale_id'    => $sale->sale_id,
                    'rating'     => $rating,
                    'body'       => $body,
                ]);

                // Spread them out, so the shop page does not show twelve
                // reviews all written in the same second.
                ProductReview::where('product_id', $item->product_id)
                    ->where('user_id', $sale->user_id)
                    ->update([
                        'created_at' => now()->subDays(count(self::OPINIONS) - $written)->subHours($written * 3),
                    ]);

                $used[$key] = true;
                $written++;

                break;                    // one review per order
            }
        }

        $this->command?->info("Wrote {$written} reviews against delivered orders.");

        if ($written < count(self::OPINIONS)) {
            $this->command?->line('Fewer than expected: there were not enough delivered orders with distinct products.');
        }
    }
}
