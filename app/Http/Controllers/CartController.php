<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\UserCart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{
    // Get the authenticated user's cart
    public function index()
    {
        $user = Auth::user();

        // Retrieve the user's cart with cart items and product details
        $cart = UserCart::with('items.product')
    ->where('user_id', $user->id)
    ->first();


        if (!$cart) {
            return response()->json(['message' => 'Cart is empty'], 404);
        }

        return response()->json([
            'cart_id' => $cart->id,
            'items' => $cart->items,
        ]);
    }

    // Add or update items in the cart
    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        // Retrieve or create the cart for the user
        $cart = UserCart::firstOrCreate(
            ['user_id' => $user->id],
            ['updated_at' => now()]
        );

        $responses = [];

        // Process each item in the request
        foreach ($validated['items'] as $item) {
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $item['product_id'])
                ->first();

            if ($cartItem) {
                // Update the quantity if the item already exists
                $cartItem->quantity = $item['quantity'];
                $cartItem->save();

                $responses[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $cartItem->quantity,
                    'message' => 'Quantity updated',
                ];
            } else {
                // Add the item to the cart
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);

                $responses[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'message' => 'Item added',
                ];
            }
        }

        $cart->touch(); // Update the cart's `updated_at` timestamp

        return response()->json([
            'message' => 'Cart updated successfully',
            'details' => $responses,
        ], 201);
    }

    // Remove a specific item from the cart
    public function removeItem($productId)
    {
        $user = Auth::user();

        // Retrieve the user's cart
        $cart = UserCart::where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $productId)
            ->first();

        if (!$cartItem) {
            return response()->json(['message' => 'Item not found in cart'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    // Clear the entire cart
    public function clearCart()
    {
        $user = Auth::user();
    
        // Retrieve the user's cart
        $cart = UserCart::where('user_id', $user->id)->first();
    
        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }
    
        // // Delete all cart items
        $cart->cartItems()->delete();
    
        return response()->json(['message' => 'Cart cleared successfully','cart'=>$cart]);
    }
    
    // Automatically delete old carts (e.g., after 7 days)
    public function deleteOldCarts()
    {
        $oldCarts = UserCart::where('updated_at', '<', Carbon::now()->subDays(7))->get();

        foreach ($oldCarts as $cart) {
            // Restore product quantities if necessary before deleting cart items
            foreach ($cart->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->quantity += $item->quantity;
                    $product->save();
                }
            }

            $cart->items()->delete();
            $cart->delete();
        }

        return response()->json(['message' => 'Old carts deleted successfully']);
    }
}
