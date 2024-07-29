<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'product_quantity' => 'required|integer|min:1',
            'user_id' => 'nullable|integer',
            'guest_id' => 'nullable|string',
        ]);

        if (empty($validated['user_id']) && empty($validated['guest_id'])) {
            return response()->json(['error' => 'Either provide user id or guest id'], 400);
        }

        $cartItem = Cart::create($validated);

        return response()->json(['message' => 'Item added to cart', 'data' => $cartItem], 201);
    }

    public function getCartItemsByUser($id)
    {
        $cartItems = Cart::where('user_id', $id)
            ->orWhere('guest_id', $id)
            ->join('product', 'cart.product_id', '=', 'product.id')
            ->select('cart.*', 'product.product_name as product_name', 'product.price as product_price')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'No cart items found for this user'], 404);
        }

        return response()->json(['message' => 'Cart items retrieved successfully', 'data' => $cartItems], 200);
    }

    public function updateCartItem(Request $request, $id)
    {
        $validated = $request->validate([
            'product_quantity' => 'required|integer|min:0',
        ]);

        $cartItem = Cart::findOrFail($id);

        if ($validated['product_quantity'] == 0) {
            $cartItem->delete();
            return response()->json(['message' => 'Cart item removed successfully'], 200);
        } else {
            $cartItem->update($validated);
            return response()->json(['message' => 'Cart item updated successfully', 'data' => $cartItem], 200);
        }
    }


    public function deleteCartItem($id)
    {
        $cartItem = Cart::findOrFail($id);

        $cartItem->delete();

        return response()->json(['message' => 'Cart item deleted successfully'], 200);
    }
}
