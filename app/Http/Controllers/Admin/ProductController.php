<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ShoppingCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    protected function productRules(?int $productId = null, ?string $unit = null): array
    {
        return [
            'product_name' => [
                'required',
                'string',
                // products.product_name is varchar(200). A longer name passed
                // this rule and then failed at the insert with a raw SQL error.
                'max:200',
                /*
                 * Unique per pack size, not outright. The shop sells one oil
                 * in several bottles and they all carry the same name -- the
                 * three Patrol 5W30 rows are one product on one page -- so a
                 * plain unique rule made it impossible to add a size to a
                 * product that already existed, which is the ordinary case.
                 *
                 * Archived products keep their name in the table, so the rule
                 * has to look past them or a name could never be reused.
                 */
                Rule::unique('products', 'product_name')
                    ->ignore($productId, 'product_id')
                    ->where('unit', $unit ?: '1 Liter')
                    ->whereNull('deleted_at'),
            ],
            'brand' => 'required|string|max:100',
            'oil_type' => 'required|in:Synthetic,Semi-Synthetic,Mineral,Coolant,Other',
            'unit' => 'nullable|string|max:50',
            'price' => 'required|integer|min:0|max:999999',
            'reorder_level' => 'nullable|integer|min:0|max:99999',
            'viscosity_grade' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'image_2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            /*
             * The shop shows one card per product_line and lets the shopper
             * pick a pack on the product page, so this is what makes a new
             * 4 litre bottle appear beside the 1 litre one instead of as a
             * second product. Only an existing line can be joined; a product
             * sold on its own is given a line of its own below.
             */
            'product_line' => [
                'nullable', 'string', 'max:100',
                Rule::exists('products', 'product_line')->whereNull('deleted_at'),
            ],
            // Shown on the product page as "published by the manufacturer".
            'source_url' => 'nullable|url|max:255',
        ];
    }

    protected function productMessages(): array
    {
        return [
            'product_name.required' => 'Product name is required.',
            'product_name.unique' => 'That product already exists in this pack size. Change the size, or edit the existing one.',
            'product_name.max' => 'Product name must not exceed 200 characters.',
            'brand.required' => 'Brand is required.',
            'oil_type.required' => 'Product type is required.',
            'price.required' => 'Price is required.',
            'price.integer' => 'Price must be a whole number.',
            'price.min' => 'Price cannot be negative.',
            'reorder_level.integer' => 'Reorder level must be a whole number.',
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'Accepted image types are JPG, JPEG, PNG, and WEBP.',
            'image.max' => 'Image size must not exceed 2 MB.',
            'image_2.max' => 'The second image must not exceed 2 MB.',
            'product_line.exists' => 'That product line no longer exists. Pick another, or sell this on its own.',
            'source_url.url' => 'The manufacturer link must be a full address, starting with https://',
        ];
    }

    // Display products page
    public function index()
    {
        return view('admin.products');
    }

    /**
     * The product lines already on sale, and the packs each one holds.
     *
     * A line is what the shop shows as a single card: the 1, 4 and 5 litre
     * bottles of one oil are three rows here and one page there. Adding a
     * pack to an existing line is how a new size joins that page rather than
     * appearing as a separate product.
     */
    public function getLines()
    {
        $lines = Product::whereNotNull('product_line')
            ->orderBy('product_name')
            ->get()
            ->groupBy('product_line')
            ->map(function ($packs, string $line) {
                $first = $packs->first();

                return [
                    'product_line' => $line,
                    'name' => $first->product_name,
                    'brand' => $first->brand,
                    'oil_type' => $first->oil_type,
                    'viscosity_grade' => $first->viscosity_grade,
                    'description' => $first->description,
                    'source_url' => $first->specifications['source'] ?? null,
                    'image_url' => $first->image_path ? asset('storage/' . $first->image_path) : null,
                    'packs' => $packs->sortBy('price')->map(fn (Product $pack) => [
                        'product_id' => $pack->product_id,
                        'unit' => $pack->unit,
                        'price' => (float) $pack->price,
                    ])->values()->all(),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => $lines]);
    }

    /**
     * A line of its own, for a product not sold in several sizes.
     *
     * Without one the row still shows in the shop -- it falls back to its own
     * id -- but nothing could ever be added to it, so a second pack size
     * would have to become a second product. Giving every product a line from
     * the start means any of them can gain a pack later.
     */
    protected function generateProductLine(string $name): string
    {
        $base = Str::limit(Str::slug($name), 94, '');
        $line = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('product_line', $line)->exists()) {
            $line = $base . '-' . $suffix++;
        }

        return $line;
    }

    /**
     * The manufacturer's link lives inside the specifications, beside the
     * copy scraped from their site, so writing it must not wipe the rest.
     */
    protected function mergeSource(?array $specifications, ?string $url): ?array
    {
        $specifications ??= [];

        if (filled($url)) {
            $specifications['source'] = $url;
        } else {
            unset($specifications['source']);
        }

        return $specifications ?: null;
    }

    // Get all products (API)
    public function getProducts(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'archived' => 'nullable|boolean',
        ]);

        // The archived list is a separate view rather than a mixed one, so a
        // product that is no longer sold cannot be edited or restocked by
        // mistake from the ordinary catalogue.
        $query = $request->boolean('archived')
            ? Product::onlyTrashed()->with('inventory')->orderByDesc('deleted_at')
            : Product::with('inventory')->orderByDesc('product_id');

        // Searching here rather than in the browser, so the term reaches the
        // whole catalogue instead of only the page already loaded.
        if ($request->filled('search')) {
            $term = trim($request->input('search'));

            $query->where(function ($q) use ($term) {
                $q->where('product_name', 'like', "%{$term}%")
                    ->orWhere('brand', 'like', "%{$term}%")
                    ->orWhere('oil_type', 'like', "%{$term}%")
                    ->orWhere('viscosity_grade', 'like', "%{$term}%");
            });
        }

        return $this->paginated($query->paginate($this->perPage()), function ($product) {
            $payload = $product->toArray();
            $payload['image_url'] = $product->image_path ? asset('storage/' . $product->image_path) : null;
            $payload['archived'] = $product->trashed();

            return $payload;
        });
    }

    // Get single product
    public function getProduct($id)
    {
        $product = Product::with('inventory')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($product->toArray(), [
                'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                'image_2_url' => $product->image_path_2 ? asset('storage/' . $product->image_path_2) : null,
                'source_url' => $product->specifications['source'] ?? null,
            ])
        ]);
    }

    // Create product
    public function store(Request $request)
    {
        $request->validate($this->productRules(null, $request->input('unit')), $this->productMessages());

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        $secondPath = $request->hasFile('image_2')
            ? $request->file('image_2')->store('products', 'public')
            : null;

        $product = Product::create([
            'product_name' => $request->product_name,
            'brand' => $request->brand,
            'product_line' => $request->product_line ?: $this->generateProductLine($request->product_name),
            'oil_type' => $request->oil_type,
            'viscosity_grade' => $request->viscosity_grade,
            'unit' => $request->unit ?: '1 Liter',
            'price' => $request->price,
            'reorder_level' => $request->reorder_level ?? 10,
            'description' => $request->description,
            'specifications' => $this->mergeSource(null, $request->source_url),
            'image_path' => $imagePath,
            'image_path_2' => $secondPath,
        ]);

        // Create inventory record
        Inventory::create([
            'product_id' => $product->product_id,
            'quantity' => 0
        ]);

        ActivityLog::logAction(
            auth()->id(),
            'product_created',
            "Created product: {$product->product_name} by " . auth()->user()->full_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Product added successfully',
            'data' => $product
        ]);
    }

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $request->validate($this->productRules((int) $id, $request->input('unit') ?: $product->unit), $this->productMessages());

        $imagePath = $product->image_path;
        $secondPath = $product->image_path_2;

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $imagePath = $request->file('image')->store('products', 'public');
        }

        if ($request->hasFile('image_2')) {
            if ($product->image_path_2) {
                Storage::disk('public')->delete($product->image_path_2);
            }

            $secondPath = $request->file('image_2')->store('products', 'public');
        }

        $product->update([
            'product_name' => $request->product_name,
            'brand' => $request->brand,
            // An existing line is kept when the form does not send one, so
            // editing a pack cannot quietly split it off its own page.
            'product_line' => $request->product_line ?: $product->product_line ?: $this->generateProductLine($request->product_name),
            'oil_type' => $request->oil_type,
            'viscosity_grade' => $request->viscosity_grade,
            'unit' => $request->unit ?: $product->unit,
            'price' => $request->price,
            'reorder_level' => $request->reorder_level ?? $product->reorder_level,
            'description' => $request->description,
            'specifications' => $this->mergeSource($product->specifications, $request->source_url),
            'image_path' => $imagePath,
            'image_path_2' => $secondPath,
        ]);

        ActivityLog::logAction(
            auth()->id(),
            'product_updated',
            "Updated product: {$product->product_name} by " . auth()->user()->full_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Archives a product: it leaves the catalogue and the shop, but the rows
     * that reference it stay readable. Products with sales used to be
     * undeletable for exactly this reason, and now they no longer need to be.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // A shopper holding an archived line in their basket would reach
        // checkout and find it gone, so it is taken out of every cart now.
        $removedFromCarts = ShoppingCart::where('product_id', $product->product_id)->delete();

        // The image is left in place. Restoring the product should bring back
        // the whole product, photograph included.
        $product->delete();

        ActivityLog::logAction(
            auth()->id(),
            'product_archived',
            "Archived product: {$product->product_name} by " . auth()->user()->full_name
        );

        return response()->json([
            'success' => true,
            'message' => $removedFromCarts > 0
                ? "Product archived. It was removed from {$removedFromCarts} " . ($removedFromCarts === 1 ? 'basket.' : 'baskets.')
                : 'Product archived. You can restore it from the archived list.',
        ]);
    }

    /** Returns an archived product to the catalogue. */
    public function restore($id)
    {
        $product = Product::onlyTrashed()->where('product_id', $id)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'No archived product with that id.'
            ], 404);
        }

        // Another product may have taken the name while this one was away.
        $nameTaken = Product::where('product_name', $product->product_name)
            ->where('product_id', '!=', $product->product_id)
            ->exists();

        if ($nameTaken) {
            return response()->json([
                'success' => false,
                'message' => 'A product called "' . $product->product_name . '" already exists. Rename that one first.',
            ], 422);
        }

        $product->restore();

        // A product archived before inventory existed, or whose row was
        // removed since, needs one again before it can be restocked.
        Inventory::firstOrCreate(
            ['product_id' => $product->product_id],
            ['quantity' => 0]
        );

        ActivityLog::logAction(
            auth()->id(),
            'product_restored',
            "Restored product: {$product->product_name} by " . auth()->user()->full_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Product restored to the catalogue.',
        ]);
    }

    public function importCatalog(Request $request)
    {
        $request->validate([
            'catalog_text' => 'required|string|max:20000',
        ]);

        $lines = preg_split('/\r\n|\r|\n/', $request->catalog_text);
        $currentBrand = null;
        $imported = 0;
        $updated = 0;
        $skipped = [];

        foreach ($lines as $index => $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            if (Str::endsWith(strtoupper($line), 'PRODUCTS')) {
                $brandName = trim(preg_replace('/\s+PRODUCTS$/i', '', $line));
                $currentBrand = strtoupper($brandName);
                continue;
            }

            if (!$currentBrand) {
                $skipped[] = 'Line ' . ($index + 1) . ': missing brand heading before product line.';
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $productName = $parts[0] ?? '';

            if ($productName === '') {
                $skipped[] = 'Line ' . ($index + 1) . ': empty product name.';
                continue;
            }

            preg_match('/\b(\d+W\d+)\b/i', $productName, $viscosityMatch);
            preg_match('/\b(\d+)\s*L\b/i', $productName, $unitMatch);

            $upperName = strtoupper($productName);
            $coolantKeywords = ['COOLANT', 'ANTIFREEZE', 'RADIATOR FLUID'];
            $otherKeywords = ['BRAKE FLUID', 'ATF', 'TRANSMISSION', 'GEAR OIL', 'HYDRAULIC', 'GREASE', 'ADBLUE', 'DEF'];

            if (collect($coolantKeywords)->contains(fn ($keyword) => str_contains($upperName, $keyword))) {
                $oilType = 'Coolant';
            } elseif (collect($otherKeywords)->contains(fn ($keyword) => str_contains($upperName, $keyword))) {
                $oilType = 'Other';
            } elseif (str_contains($upperName, 'FULLY SYNTHETIC') || str_contains($upperName, 'FULL SYNTHETIC')) {
                $oilType = 'Synthetic';
            } elseif (str_contains($upperName, 'SEMI-SYNTHETIC') || str_contains($upperName, 'SEMI SYNTHETIC')) {
                $oilType = 'Semi-Synthetic';
            } else {
                $oilType = 'Mineral';
            }

            $unit = isset($unitMatch[1])
                ? ((int) $unitMatch[1] === 1 ? '1 Liter' : ((int) $unitMatch[1] . ' Liters'))
                : '1 Liter';

            $price = isset($parts[1]) && $parts[1] !== '' ? (int) preg_replace('/[^\d]/', '', $parts[1]) : 0;
            $stock = isset($parts[2]) && $parts[2] !== '' ? (int) preg_replace('/[^\d]/', '', $parts[2]) : 0;
            $reorderLevel = isset($parts[3]) && $parts[3] !== '' ? (int) preg_replace('/[^\d]/', '', $parts[3]) : 10;

            $product = Product::where('product_name', $productName)->first();

            if ($product) {
                $product->update([
                    'brand' => $currentBrand,
                    'oil_type' => $oilType,
                    'viscosity_grade' => $viscosityMatch[1] ?? null,
                    'unit' => $unit,
                    'price' => $price,
                    'reorder_level' => $reorderLevel ?: 10,
                    'description' => $product->description ?: ($currentBrand . ' product imported from bulk catalog.'),
                ]);

                Inventory::updateOrCreate(
                    ['product_id' => $product->product_id],
                    ['quantity' => $stock]
                );

                $updated++;
                continue;
            }

            $product = Product::create([
                'product_name' => $productName,
                'brand' => $currentBrand,
                'oil_type' => $oilType,
                'viscosity_grade' => $viscosityMatch[1] ?? null,
                'unit' => $unit,
                'price' => $price,
                'reorder_level' => $reorderLevel ?: 10,
                'description' => $currentBrand . ' product imported from bulk catalog.',
            ]);

            Inventory::create([
                'product_id' => $product->product_id,
                'quantity' => $stock,
            ]);

            $imported++;
        }

        ActivityLog::logAction(
            auth()->id(),
            'product_catalog_imported',
            "Bulk imported catalog entries. Added {$imported}, updated {$updated}."
        );

        return response()->json([
            'success' => true,
            'message' => "Catalog import completed. Added {$imported}, updated {$updated}.",
            'data' => [
                'imported' => $imported,
                'updated' => $updated,
                'skipped' => $skipped,
            ],
        ]);
    }

}