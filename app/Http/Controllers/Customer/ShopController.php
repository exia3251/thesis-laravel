<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    // Display shop page
    public function index()
    {
        return view('customer.shop');
    }

    public function show($productId)
    {
        $product = Product::with('inventory')->findOrFail($productId);

        // Every pack size of the same oil, so the page can offer them as a
        // choice instead of making the customer go back and find the 4L as
        // though it were an unrelated product.
        $packs = $this->packsOf($product);

        return view('customer.product-details', compact('product', 'packs'));
    }

    /**
     * The sibling rows that are the same oil in a different pack, smallest
     * first, including this one.
     *
     * A row with no product_line is its own line. That is what an older row
     * or one added by hand in the back office looks like, and it should still
     * render rather than disappearing into someone else's group.
     */
    private function packsOf(Product $product)
    {
        $query = Product::with('inventory');

        $product->product_line
            ? $query->where('product_line', $product->product_line)
            : $query->where('product_id', $product->product_id);

        return $query->orderBy('price')->get();
    }

    /**
     * The catalogue, one entry per oil rather than one per pack size.
     *
     * The shop used to list "... 5W30 1L" and "... 5W30 4L" as separate
     * cards, so the same oil filled the grid three times over. Sizes are now
     * carried inside the card and chosen on the product page.
     */
    public function getProducts()
    {
        $lines = Product::with('inventory')
            ->orderBy('product_name')
            ->orderBy('price')
            ->get()
            // A row without a line key stands alone rather than joining a
            // group it was never meant to be part of.
            ->groupBy(fn (Product $p) => $p->product_line ?: 'product-' . $p->product_id)
            ->map(function ($packs) {
                $packs = $packs->sortBy('price')->values();

                // The card links to a pack someone can actually buy. Falling
                // back to the cheapest keeps a sold-out line clickable so the
                // customer can see it exists and what it costs.
                $default = $packs->firstWhere(fn (Product $p) => ($p->inventory->quantity ?? 0) > 0)
                    ?? $packs->first();

                $withImage = $packs->firstWhere(fn (Product $p) => filled($p->image_path));

                return [
                    'product_line'    => $default->product_line,
                    'product_id'      => $default->product_id,
                    'product_name'    => $default->product_name,
                    'brand'           => $default->brand,
                    'oil_type'        => $default->oil_type,
                    'viscosity_grade' => $default->viscosity_grade,
                    'description'     => $default->description,
                    'unit'            => $default->unit,
                    'price'           => $default->price,
                    'from_price'      => (float) $packs->min('price'),
                    'quantity'        => $packs->sum(fn (Product $p) => $p->inventory->quantity ?? 0),
                    'pack_count'      => $packs->count(),
                    'image_url'       => $withImage ? asset('storage/' . $withImage->image_path) : null,
                    'packs'           => $packs->map(fn (Product $p) => [
                        'product_id' => $p->product_id,
                        'unit'       => $p->unit,
                        'price'      => (float) $p->price,
                        'quantity'   => $p->inventory->quantity ?? 0,
                    ])->all(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $lines,
        ]);
    }

    // Get top sold products that are in stock, falling back down the sales rank if out of stock
    public function getFeaturedProducts()
    {
        // Get all products ranked by total units sold (descending)
        // Written as a query builder join rather than through the model, so
        // the archived-product scope has to be applied by hand here.
        $topSold = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('inventory', 'products.product_id', '=', 'inventory.product_id')
            ->whereNull('products.deleted_at')
            ->select(
                'products.product_id',
                'products.product_line',
                'products.product_name',
                'products.brand',
                'products.oil_type',
                'products.viscosity_grade',
                'products.unit',
                'products.price',
                'products.image_path',
                'inventory.quantity',
                DB::raw('SUM(sale_items.quantity) as total_sold')
            )
            ->groupBy(
                'products.product_id',
                'products.product_line',
                'products.product_name',
                'products.brand',
                'products.oil_type',
                'products.viscosity_grade',
                'products.unit',
                'products.price',
                'products.image_path',
                'inventory.quantity'
            )
            ->orderByDesc('total_sold')
            ->get();

        // How many units each oil has sold, counted across its pack sizes.
        // Ranking by pack would have let one oil take all three slots by
        // appearing as its 1L, its 4L and its 5L.
        $soldByLine = $topSold
            ->groupBy(fn ($row) => $row->product_line ?: 'product-' . $row->product_id)
            ->map(fn ($rows) => $rows->sum('total_sold'));

        // Everything in the catalogue, already grouped and shaped the way a
        // card wants it, so featured and the grid cannot disagree about what
        // a line costs or how much of it is left.
        $lines = collect($this->getProducts()->getData(true)['data']);

        $featured = $lines
            ->filter(fn ($line) => $line['quantity'] > 0)
            ->map(function ($line) use ($soldByLine) {
                $line['total_sold'] = (int) ($soldByLine[$line['product_line'] ?: 'product-' . $line['product_id']] ?? 0);

                return $line;
            })
            // Best sellers first; with nothing sold yet this falls back to
            // name order, which is why a fresh install still has a strip.
            ->sortByDesc('total_sold')
            ->take(3)
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $featured,
        ]);
    }
}