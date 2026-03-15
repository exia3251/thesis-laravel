<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ShoppingCart;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Http\Request;

class CartController extends Controller
{
    // Get user's cart
    public function getCart()
    {
        $cart = ShoppingCart::with(['product.inventory'])
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($item) {
                return [
                    'cart_id' => $item->cart_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->product_name,
                    'brand' => $item->product->brand,
                    'unit' => $item->product->unit,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'stock' => $item->product->inventory->quantity ?? 0,
                    'subtotal' => $item->product->price * $item->quantity
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cart
        ]);
    }

    // Add to cart
    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'integer|min:1'
        ]);

        $quantity = $request->quantity ?? 1;

        // Check stock
        $inventory = Inventory::where('product_id', $request->product_id)->first();
        if (!$inventory || $inventory->quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        // Check if already in cart
        $cartItem = ShoppingCart::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $quantity;
            
            if ($newQuantity > $inventory->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more than available stock'
                ], 400);
            }

            $cartItem->quantity = $newQuantity;
            $cartItem->save();
        } else {
            // Add new item
            ShoppingCart::create([
                'user_id' => auth()->id(),
                'product_id' => $request->product_id,
                'quantity' => $quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Added to cart'
        ]);
    }

    // Update cart quantity
    public function updateCart(Request $request, $cartId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = ShoppingCart::where('cart_id', $cartId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        // Check stock
        $inventory = Inventory::where('product_id', $cartItem->product_id)->first();
        if ($request->quantity > $inventory->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock. Available: ' . $inventory->quantity
            ], 400);
        }

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cart updated'
        ]);
    }

    // Remove from cart
    public function removeFromCart($cartId)
    {
        $cartItem = ShoppingCart::where('cart_id', $cartId)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cartItem) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart'
        ]);
    }

    // Clear cart
    public function clearCart()
    {
        ShoppingCart::where('user_id', auth()->id())->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }
}