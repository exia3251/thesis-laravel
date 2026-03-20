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

    // Get top sold products that are in stock, falling back down the sales rank if out of stock
    public function getFeaturedProducts()
    {
        // Get all products ranked by total units sold (descending)
        $topSold = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.product_id')
            ->join('inventory', 'products.product_id', '=', 'inventory.product_id')
            ->select(
                'products.product_id',
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

        // Walk down the ranked list, pick in-stock ones until we have 3
        $featured = $topSold
            ->filter(fn($p) => $p->quantity > 0)
            ->take(3)
            ->values()
            ->map(function ($product) {
                return [
                    'product_id'      => $product->product_id,
                    'product_name'    => $product->product_name,
                    'brand'           => $product->brand,
                    'oil_type'        => $product->oil_type,
                    'viscosity_grade' => $product->viscosity_grade,
                    'unit'            => $product->unit,
                    'price'           => $product->price,
                    'quantity'        => $product->quantity,
                    'total_sold'      => $product->total_sold,
                    'image_url'       => $product->image_path
                                        ? asset('storage/' . $product->image_path)
                                        : null,
                ];
            });

        // If fewer than 3 have been sold, pad with in-stock products not already included
        if ($featured->count() < 3) {
            $featuredIds = $featured->pluck('product_id')->toArray();
            $padding = Product::with('inventory')
                ->whereHas('inventory', fn($q) => $q->where('quantity', '>', 0))
                ->whereNotIn('product_id', $featuredIds)
                ->orderBy('product_name')
                ->take(3 - $featured->count())
                ->get()
                ->map(function ($product) {
                    return [
                        'product_id'      => $product->product_id,
                        'product_name'    => $product->product_name,
                        'brand'           => $product->brand,
                        'oil_type'        => $product->oil_type,
                        'viscosity_grade' => $product->viscosity_grade,
                        'unit'            => $product->unit,
                        'price'           => $product->price,
                        'quantity'        => $product->inventory->quantity ?? 0,
                        'total_sold'      => 0,
                        'image_url'       => $product->image_path
                                            ? asset('storage/' . $product->image_path)
                                            : null,
                    ];
                });

            $featured = $featured->concat($padding)->values();
        }

        return response()->json([
            'success' => true,
            'data'    => $featured,
        ]);
    }
}