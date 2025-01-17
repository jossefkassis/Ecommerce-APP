<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\UserCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class OrderController extends Controller
{
    // Checkout: Create an order from the user's cart
 // Checkout: Create an order from the user's cart
public function checkout(Request $request)
{
    $user = Auth::user();

    // Validate the cart items sent in the request
    $validatedData = $request->validate([
        'cart.items' => 'required|array|min:1',
        'cart.items.*.product_id' => 'required|exists:products,id',
        'cart.items.*.quantity' => 'required|integer|min:1',
        'email' => 'nullable|email',
        'phone' => 'nullable|string',
        'address' => 'nullable|string',
    ]);

    $cartItems = $validatedData['cart']['items'];

    // Calculate total price
    $totalPrice = 0;

    foreach ($cartItems as $item) {
        $product = Product::find($item['product_id']);

        // Check stock availability
        if ($product->quantity < $item['quantity']) {
            return response()->json([
                'message' => 'Insufficient stock for ' . $product->title,
            ], 400);
        }

        $price = ($product->discount_price && $product->discount_price > 0)
    ? $product->discount_price
    : $product->price;
        $totalPrice += $item['quantity'] * $price;
    }

    DB::beginTransaction();

    try {
        // Create the order
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'email' => $request->input('email', $user->email),
            'phone' => $request->input('phone', $user->phone),
            'address' => $request->input('address', $user->address),
        ]);

        // Create order items and reduce product quantity
        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);

            // Create the order item
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $product->price,
                'title' => $product->title,
                'image_url' => $product->featured_image,
            ]);

            // Reduce product quantity
            $product->decrement('quantity', $item['quantity']);
            if ($product->quantity == 0) {
                $product->update(['in_stock' => 0]);
            }
        }

        $userCart = UserCart::where('user_id', $user->id)->first();
        if ($userCart) {
            $userCart->items()->delete(); // Delete cart items
            $userCart->delete(); // Delete the cart
        }

        DB::commit();
     
        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => 'Failed to place order',
            'error' => $e->getMessage(),
        ], 500);
    }
}



    // Get all orders for the authenticated user
    public function getUserOrders()
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)->with('items')->get();
        return response()->json($orders);
    }

    // Get a specific order by ID for the authenticated user
    public function getUserOrder($id)
    {
        $user = Auth::user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->with('items')->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    
// Cancel an order (only if pending)
public function cancelOrder($id)
{
    $user = Auth::user();
    $order = Order::where('id', $id)
        ->where('user_id', $user->id)
        ->where('status', 'pending')
        ->with('items') // Use the correct relationship name
        ->first();

    if (!$order) {
        return response()->json(['message' => 'Order not found or cannot be canceled'], 400);
    }

    // Restore product quantities
    foreach ($order->items as $item) {
        $product = Product::find($item->product_id);
        if ($product) {
            $product->increment('quantity', $item->quantity);
        }
    }

    $order->update(['status' => 'canceled']);

    return response()->json(['message' => 'Order canceled successfully', 'order' => $order],200);
}



    // Admin: Get all orders
    public function getAllOrders()
    {
        $orders = Order::with('items', 'user:id,name,email')->get();
        return response()->json($orders);
    }

    // Admin: Get all orders by user ID
    public function getOrdersByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->with('items', 'user:id,name,email')->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders found for this user'], 404);
        }

        return response()->json($orders);
    }

    // Admin: Get order by ID
    public function adminGetOrderById($id)
    {
        $order = Order::with('items', 'user:id,name,email')->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // Admin: Change the status of an order
    public function changeOrderStatus(Request $request, $id)
    {
        $order = Order::with('items')->find($id);
    
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    
        // Ensure the order is in 'pending' status
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can have their status changed'], 400);
        }
    
        $validatedData = $request->validate([
            'status' => 'required|in:completed,rejected',
        ]);
    
        if ($validatedData['status'] === 'rejected') {
            // Restore product quantities for rejected orders
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('quantity', $item->quantity);

                    if ($product->quantity > 0 && $product->in_stock === 0) {
                        $product->update(['in_stock' => 1]);
                    }
                }
            }
        }
    
        // Update the order status
        $order->update(['status' => $validatedData['status']]);
    
        return response()->json([
            'message' => 'Order status updated successfully',
            'order' => $order,
        ]);
    }
    public function getBestSellingProducts()
{
    $bestSellingProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'completed') // Only include completed orders
        ->groupBy('product_id')
        ->orderBy('total_sold', 'desc')
        ->take(10) // Limit to top 10 best-selling products
        ->get();
        
    // Fetch product details along with category and shop for each best-selling product
    $bestSellingProductsWithDetails = $bestSellingProducts->map(function ($orderItem) {
        $product = Product::with(['category', 'shop'])->find($orderItem->product_id);
        
        // Add total_sold to the product object
        if ($product) {
            $product->total_sold = $orderItem->total_sold;
        }

        return $product;
    });

    return response()->json($bestSellingProductsWithDetails);
}
  

}
