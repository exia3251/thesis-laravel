<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        return view('customer.product-details', compact('product'));
    }

    // Get all products with stock
    public function getProducts()
    {
        $products = Product::with('inventory')
            ->orderBy('product_name')
            ->get()
            ->map(function ($product) {
                return [
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'brand' => $product->brand,
                    'oil_type' => $product->oil_type,
                    'viscosity_grade' => $product->viscosity_grade,
                    'unit' => $product->unit,
                    'price' => $product->price,
                    'description' => $product->description,
                    'quantity' => $product->inventory->quantity ?? 0,
                    'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function getProduct($productId)
    {
        $product = Product::with('inventory')->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'brand' => $product->brand,
                'oil_type' => $product->oil_type,
                'viscosity_grade' => $product->viscosity_grade,
                'unit' => $product->unit,
                'price' => $product->price,
                'description' => $product->description,
                'quantity' => $product->inventory->quantity ?? 0,
                'reorder_level' => $product->reorder_level,
                'image_url' => $product->image_path ? asset('storage/' . $product->image_path) : null,
            ]
        ]);
    }
}
