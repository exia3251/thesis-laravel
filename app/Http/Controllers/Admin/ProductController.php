<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
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
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    // Create product
    public function store(Request $request)
    {
        $request->validate([
            'product_name' => 'required|unique:products,product_name|max:255',
            'brand' => 'required|max:100',
            'oil_type' => 'required|in:Synthetic,Semi-Synthetic,Mineral',
            'unit' => 'nullable|max:50',
            'price' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'viscosity_grade' => 'nullable|max:20',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

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

        $request->validate([
            'product_name' => 'required|max:255|unique:products,product_name,'.$id.',product_id',
            'brand' => 'required|max:100',
            'oil_type' => 'required|in:Synthetic,Semi-Synthetic,Mineral',
            'unit' => 'nullable|max:50',
            'price' => 'required|numeric|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'viscosity_grade' => 'nullable|max:20',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

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

}
