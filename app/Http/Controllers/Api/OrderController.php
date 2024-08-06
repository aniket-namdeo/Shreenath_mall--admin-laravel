<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Order_items;
use App\Models\User_addresses;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'address_id' => 'required',
            'coupon_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'shipping_fee' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|integer',
            'items.*.price' => 'required|numeric',
        ]);

        try {

            $orderDate = $request->order_date ?: date('Y-m-d');
            $deliveryDate = $request->delivery_date ?: date('Y-m-d');

            $order = Order::create([
                'user_id' => $request->user_id,
                'total_amount' => $request->total_amount,
                'status' => "pending",
                'delivery_status' => "pending",
                'payment_method' => $request->payment_method,
                'payment_status' => "unpaid",
                'order_date' => $orderDate,
                'delivery_date' => $deliveryDate,
                'address_id' => $request->address_id,
                'coupon_code' => $request->coupon_code,
                'discount_amount' => $request->discount_amount,
                'tax_amount' => $request->tax_amount,
                'shipping_fee' => $request->shipping_fee,
            ]);

            foreach ($request->items as $item) {
                Order_items::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            foreach ($request->items as $item) {
                Cart::where('id', $item['cart_id'])->delete();
            }

            return response()->json(['order' => $order, 'message' => 'Order created successfully'], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create order', 'message' => $e->getMessage()], 500);
        }
    }

    public function getOrdersByUser($userId)
    {
        $orders = Order::where('orders.user_id', $userId)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('user_addresses', 'orders.address_id', '=', 'user_addresses.id')
            ->join('product', 'order_items.product_id', '=', 'product.id')
            ->select(
                'orders.id as order_id',
                'orders.user_id',
                'orders.total_amount',
                'orders.status',
                'orders.delivery_status',
                'orders.payment_method',
                'orders.payment_status',
                'orders.order_date',
                'orders.delivery_date',
                'orders.address_id',
                'orders.coupon_code',
                'orders.discount_amount',
                'orders.tax_amount',
                'orders.shipping_fee',
                'order_items.product_id',
                'order_items.quantity',
                'order_items.price',
                'product.product_name as product_name',
                'product.mrp as product_mrp',
                'product.price as product_price',
                'product.image_url1 as product_image_url',
                'user_addresses.house_address',
                'user_addresses.street_address',
                'user_addresses.landmark',
                'user_addresses.city',
                'user_addresses.state',
                'user_addresses.country',
                'user_addresses.pincode'
            )
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for this user'], 404);
        }

        $groupedOrders = $orders->groupBy('order_id');

        $result = $groupedOrders->map(function ($order) {
            $orderData = $order->first();
            $items = $order->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'product_name' => $item->product_name,
                    'product_mrp' => $item->product_mrp,
                    'product_price' => $item->product_price,
                    'product_image_url' => $item->product_image_url,
                ];
            });

            return [
                'id' => $orderData->order_id,
                'user_id' => $orderData->user_id,
                'total_amount' => $orderData->total_amount,
                'status' => $orderData->status,
                'delivery_status' => $orderData->delivery_status,
                'payment_method' => $orderData->payment_method,
                'payment_status' => $orderData->payment_status,
                'order_date' => $orderData->order_date,
                'delivery_date' => $orderData->delivery_date,
                'address_id' => $orderData->address_id,
                'coupon_code' => $orderData->coupon_code,
                'discount_amount' => $orderData->discount_amount,
                'tax_amount' => $orderData->tax_amount,
                'shipping_fee' => $orderData->shipping_fee,
                'house_address' => $orderData->house_address,
                'street_address' => $orderData->street_address,
                'landmark' => $orderData->landmark,
                'city' => $orderData->city,
                'state' => $orderData->state,
                'country' => $orderData->country,
                'pincode' => $orderData->pincode,
                'items' => $items
            ];
        });

        return response()->json(['orders' => $result->values()], 200);
    }

    public function getOrderDetail($id)
    {
        $orders = $orders = Order::where('orders.id', $id)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('user_addresses', 'orders.address_id', '=', 'user_addresses.id')
            ->join('product', 'order_items.product_id', '=', 'product.id')
            ->select(
                'orders.id as order_id',
                'orders.user_id',
                'orders.total_amount',
                'orders.status',
                'orders.delivery_status',
                'orders.payment_method',
                'orders.payment_status',
                'orders.order_date',
                'orders.delivery_date',
                'orders.address_id',
                'orders.coupon_code',
                'orders.discount_amount',
                'orders.tax_amount',
                'orders.shipping_fee',
                'order_items.product_id',
                'order_items.quantity',
                'order_items.price',
                'product.product_name as product_name',
                'product.mrp as product_mrp',
                'product.price as product_price',
                'product.image_url1 as product_image_url',
                'user_addresses.house_address',
                'user_addresses.street_address',
                'user_addresses.landmark',
                'user_addresses.city',
                'user_addresses.state',
                'user_addresses.country',
                'user_addresses.pincode'
            )
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders with this id'], 404);
        }

        $groupedOrders = $orders->groupBy('order_id');

        $result = $groupedOrders->map(function ($order) {
            $orderData = $order->first();
            $items = $order->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'product_name' => $item->product_name,
                    'product_mrp' => $item->product_mrp,
                    'product_price' => $item->product_price,
                    'product_image_url' => $item->product_image_url,
                ];
            });

            return [
                'id' => $orderData->order_id,
                'user_id' => $orderData->user_id,
                'total_amount' => $orderData->total_amount,
                'status' => $orderData->status,
                'delivery_status' => $orderData->delivery_status,
                'payment_method' => $orderData->payment_method,
                'payment_status' => $orderData->payment_status,
                'order_date' => $orderData->order_date,
                'delivery_date' => $orderData->delivery_date,
                'address_id' => $orderData->address_id,
                'coupon_code' => $orderData->coupon_code,
                'discount_amount' => $orderData->discount_amount,
                'tax_amount' => $orderData->tax_amount,
                'shipping_fee' => $orderData->shipping_fee,
                'house_address' => $orderData->house_address,
                'street_address' => $orderData->street_address,
                'landmark' => $orderData->landmark,
                'city' => $orderData->city,
                'state' => $orderData->state,
                'country' => $orderData->country,
                'pincode' => $orderData->pincode,
                'items' => $items
            ];
        });

        return response()->json(['orders' => $result->values()], 200);

    }

    public function paymentStatusUpdate(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);
    
        $order = Order::find($id);
    
        $transaction_time = date('Y-m-d H:m:s');

        if ($order) {
            $order->update([
                'transaction_id' => $request->transaction_id,
                'transaction_time' => $transaction_time,
            ]);
    
            return response()->json([
                'message' => 'Transaction details updated successfully.',
                'order' => $order
            ], 200);
        } else {
            return response()->json([
                'message' => 'Order not found.'
            ], 404);
        }
    }

    public function cancelOrder($id)
    {
        $order = Order::find($id);
        if ($order) {
            DB::beginTransaction();
            try {
                $order->update([
                    'status' => 'cancelled'
                ]);
                $order->orderItems()->update(['isCancelled' => 1]);
                DB::commit();
                return response()->json(['message' => 'Order and its items cancelled'], 200);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json(['error' => 'Failed to cancel order and its items'], 500);
            }
        } else {
            return response()->json(['error' => 'No order with this id'], 404);
        }
    }


    public function cancelOrdersItems($id)
    {
        $checkData = Order_items::find($id);
        if ($checkData) {
            $checkData->update([
                'isCancelled' => 1
            ]);
            return response()->json(['message' => 'Order Item cancelled'], 201);
        } else {
            return response()->json(['error' => 'No items with this order id'], 500);
        }
    }
}