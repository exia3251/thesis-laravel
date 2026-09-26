<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The shape of the price list, rather than the prices in it.
 *
 * Prices are the business's to set and will change. What must not change is
 * that they hang together: a bigger container costs less per litre than a
 * smaller one, nothing is free, and nothing costs more than a customer can
 * actually send.
 *
 * The first of those was written after getting it wrong. The 200 litre drum
 * was worked out by marking up a wholesale figure and came to PHP 406 the
 * litre, against PHP 350 for the five litre bottle -- a drum dearer per litre
 * than the bottle, which nobody would buy and nobody would sell.
 */
class CataloguePricingTest extends TestCase
{
    use RefreshDatabase;

    /** The litres in a pack, read off its own label. */
    private function litres(Product $product): float
    {
        preg_match('/([\d.]+)/', (string) $product->unit, $matches);

        return (float) ($matches[1] ?? 1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductCatalogSeeder::class);
    }

    #[Test]
    public function a_bigger_pack_never_costs_more_per_litre(): void
    {
        foreach (Product::all()->groupBy('product_line') as $line => $packs) {
            $rates = $packs
                ->map(fn (Product $p) => [
                    'unit' => $p->unit,
                    'litres' => $this->litres($p),
                    'rate' => (float) $p->price / max($this->litres($p), 0.001),
                ])
                ->sortBy('litres')
                ->values();

            for ($i = 1; $i < $rates->count(); $i++) {
                $this->assertLessThanOrEqual(
                    $rates[$i - 1]['rate'] + 0.01,
                    $rates[$i]['rate'],
                    sprintf(
                        '%s: a %s works out at PHP %.2f the litre, dearer than the %s at PHP %.2f.',
                        $line, $rates[$i]['unit'], $rates[$i]['rate'], $rates[$i - 1]['unit'], $rates[$i - 1]['rate']
                    )
                );
            }
        }
    }

    #[Test]
    public function nothing_is_free_and_nothing_is_unpayable(): void
    {
        $this->assertSame(0, Product::where('price', '<=', 0)->count(), 'A product priced at nothing can be ordered for nothing.');

        // A fully verified GCash wallet stops at PHP 100,000, and GCash is the
        // only online method this shop offers. Anything dearer is listed but
        // cannot be paid for.
        $this->assertSame(
            0,
            Product::where('price', '>', 100000)->count(),
            'Listed above what a GCash wallet can send, with no other online method offered.'
        );
    }

    #[Test]
    public function every_product_can_be_stocked_and_can_gain_a_size(): void
    {
        $this->assertSame(0, Product::doesntHave('inventory')->count(), 'Without an inventory row a product can never be stocked.');

        $this->assertSame(
            0,
            Product::whereNull('product_line')->count(),
            'Without a line a product can never gain a second pack size.'
        );
    }

    #[Test]
    public function one_specification_carries_one_price_across_brands(): void
    {
        // The supplier's list is arranged by viscosity and API grade rather
        // than by brand, so two brands meeting one specification are priced
        // alike. If that ever stops being true it should be a decision, not
        // a drift.
        /*
         * Grouped on the product line with its brand stripped off, not on the
         * viscosity grade: DEXRON III and DEXRON VI both record their grade
         * as "ATF" while being different specifications at different prices,
         * so grading alone put them in one bucket and called it a fault.
         */
        $byGradeAndPack = Product::whereNotNull('product_line')
            ->get()
            ->groupBy(fn (Product $p) => preg_replace('/^(canroyal|solar|patrol)-/', '', $p->product_line) . '|' . $p->unit);

        foreach ($byGradeAndPack as $key => $group) {
            $prices = $group->pluck('price')->map(fn ($p) => (float) $p)->unique();

            $this->assertCount(
                1,
                $prices,
                "{$key} is sold at " . $prices->implode(' and ') . ' depending on the brand.'
            );
        }
    }
}
