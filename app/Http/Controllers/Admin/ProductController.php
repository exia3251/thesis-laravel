<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
use Illuminate\Http\Request;

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
        $products = Product::with(['supplier', 'inventory'])
            ->orderBy('product_id', 'desc')
            ->get();

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
            'oil_type' => 'required|in:Synthetic,Semi-Synthetic,Mineral,Other',
            'unit' => 'required|max:50',
            'price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        $product = Product::create($request->all());

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
            'oil_type' => 'required|in:Synthetic,Semi-Synthetic,Mineral,Other',
            'unit' => 'required|max:50',
            'price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);

        $product->update($request->all());

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

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    // Get suppliers for dropdown
    public function getSuppliers()
    {
        $suppliers = Supplier::orderBy('supplier_name')->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }
}