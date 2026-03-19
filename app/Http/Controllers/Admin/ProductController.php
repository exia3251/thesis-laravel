<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Product;
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
                Rule::unique('products', 'product_name')->ignore($productId, 'product_id'),
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
    public function getProducts()
    {
        $products = Product::with('inventory')
            ->orderBy('product_id', 'desc')
            ->get()
            ->map(function ($product) {
                $payload = $product->toArray();
                $payload['image_url'] = $product->image_path ? asset('storage/' . $product->image_path) : null;

                return $payload;
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
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

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product
        ]);
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if product has sales
        if ($product->saleItems()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with existing sales'
            ], 400);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
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
            "Bulk imported catalog entries. Added {$imported}, updated {$updated}.",
            $request->ip()
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
