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

        $cartItem = Cart::where('product_id', $validated['product_id'])
            ->when($validated['user_id'], function ($query, $userId) {
                return $query->where('user_id', $userId);
            })
            ->when($validated['guest_id'], function ($query, $guestId) {
                return $query->where('guest_id', $guestId);
            })
            ->first();

        if ($cartItem) {
            $cartItem->product_quantity += $validated['product_quantity'];
            $cartItem->save();

            return response()->json(['status' => true, 'message' => 'Cart item quantity updated', 'data' => $cartItem], 200);
        } else {
            $newCartItem = Cart::create($validated);

            if ($newCartItem) {
                return response()->json(['status' => true, 'message' => 'Item added to cart', 'data' => $newCartItem], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Item not added to cart', 'data' => null], 500);
            }
        }
    }

    public function getCartItemsByUser($id)
    {
        $cartItems = Cart::where('user_id', $id)
            ->orWhere('guest_id', $id)
            ->join('product', 'cart.product_id', '=', 'product.id')
            ->select('cart.id', 'cart.product_id', 'cart.product_quantity', 'cart.user_id', 'cart.guest_id', 'product.product_name as product_name', 'product.price as product_price', 'product.mrp as product_mrp', 'product.image_url1 as product_image', 'product.pack_size')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'No cart items found for this user'], 404);
        }

        if ($cartItems) {
            $cartItems = $cartItems->map(function ($item) {
                $item->product_price = (int) $item->product_price;
                $item->product_mrp = (int) $item->product_mrp;
                return $item;
            });

            $totalPrice = $cartItems->sum(function ($item) {
                return $item->product_price * $item->product_quantity;
            });

            $totalMrp = $cartItems->sum(function ($item) {
                return $item->product_mrp * $item->product_quantity;
            });
            return response()->json([
                'status' => true,
                'message' => 'Cart items retrieved successfully',
                'data' => $cartItems,
                'total_price' => $totalPrice,
                'total_mrp' => $totalMrp,
                'handling_charge' => "5",
                'deliveery_charge' => "30"

            ], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'No cart items added.', 'data' => null], 500);

        }
    }

    public function updateCartItem(Request $request, $id)
    {
        $validated = $request->validate([
            'product_quantity' => 'required|integer|min:0',
        ]);
        $cartItem = Cart::findOrFail($id);
        if (!$cartItem) {
            return response()->json(['message' => 'No items to delete ', 'data' => $cartItem], 200);
        }
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
