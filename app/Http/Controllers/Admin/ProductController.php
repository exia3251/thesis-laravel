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
    protected function productRules(?int $productId = null): array
    {
        return [
            'product_name' => [
                'required',
                'string',
                'max:255',
                // Archived products keep their name in the table, so the rule
                // has to look past them or a name could never be reused.
                Rule::unique('products', 'product_name')
                    ->ignore($productId, 'product_id')
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
        ];
    }

    protected function productMessages(): array
    {
        return [
            'product_name.required' => 'Product name is required.',
            'product_name.unique' => 'That product name already exists.',
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
        ];
    }

    // Display products page
    public function index()
    {
        return view('admin.products');
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
            ])
        ]);
    }

    // Create product
    public function store(Request $request)
    {
        $request->validate($this->productRules(), $this->productMessages());

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;

        $product = Product::create([
            'product_name' => $request->product_name,
            'brand' => $request->brand,
            'oil_type' => $request->oil_type,
            'viscosity_grade' => $request->viscosity_grade,
            'unit' => $request->unit ?: '1 Liter',
            'price' => $request->price,
            'reorder_level' => $request->reorder_level ?? 10,
            'description' => $request->description,
            'image_path' => $imagePath,
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

        $request->validate($this->productRules((int) $id), $this->productMessages());

        $imagePath = $product->image_path;

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }

            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'product_name' => $request->product_name,
            'brand' => $request->brand,
            'oil_type' => $request->oil_type,
            'viscosity_grade' => $request->viscosity_grade,
            'unit' => $request->unit ?: $product->unit,
            'price' => $request->price,
            'reorder_level' => $request->reorder_level ?? $product->reorder_level,
            'description' => $request->description,
            'image_path' => $imagePath,
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