<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\User;
use App\Models\UserCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'required|string',
        ]);
    
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'address' => $validatedData['address'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user',

        ]);
    
        $token = $user->createToken($user->name . 'auth_token')->plainTextToken;
    
        return response()->json([
            'status'=>true,
            'message' => 'Registration successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
    


     /**
     * create  a new user with rolefor admins only.
     */
    public function createUser(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:user,admin', // Ensures role is either 'user' or 'admin'
        ]);
    
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
        ]);
    
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
        ],201);
    }
    
 /**
 * Log in an existing user.
 */
public function login(Request $request)
{
    $credentials = $request->validate([
        'identifier' => 'required|string', // Can be email or phone
        'password' => 'required|string',
    ]);

    // Check if the identifier is an email or phone
    $user = User::where('email', $credentials['identifier'])
                ->orWhere('phone', $credentials['identifier'])
                ->first();

    if (!$user || !Hash::check($credentials['password'], $user->password)) {
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }

    $token = $user->createToken($user->name . 'auth_token')->plainTextToken;

    // Fetch the user's cart
    $cart = UserCart::with('items.product')
        ->where('user_id', $user->id)
        ->first();

    // Format cart data
    $cartData = $cart ? [
        'cart_id' => $cart->id,
        'items' => $cart->items->map(function ($item) {
            return [
                'product_id' => $item->product->id,
                'product_name' => $item->product->title,
                'price' => $item->product->price,
                'discount_price' => $item->product->discount_price,
                'available_quantity' => $item->product->quantity,
                'quantity' => $item->quantity,
                'image_url' => $item->product->featured_image,
            ];
        }),
    ] : null;

    // Fetch the user's favorites
    $favorites = $user->favorites()->with('product')->get();
    $favoritesData = $favorites->map(function ($favorite) {
        return [
            'product_id' => $favorite->product->id,
        ];
    });

    return response()->json([
        'status' => true,
        'message' => 'Login successful',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer',
        'cart' => $cartData, // Include cart data
        'favorites' => $favoritesData, // Include favorites data
    ]);
}



     /**
     * Log out the authenticated user.
     */
    public function logout(Request $request)
    {
        $user = $request->user();
    
        // Check if the cart data is provided in the request
        if ($request->has('cart')) {
            $cartData = $request->validate([
                'cart.items' => 'nullable|array',
                'cart.items.*.product_id' => 'required_with:cart.items|exists:products,id',
                'cart.items.*.quantity' => 'required_with:cart.items|integer|min:1',
            ]);
    
            // Retrieve or create the user's cart
            $userCart = UserCart::firstOrCreate(
                ['user_id' => $user->id],
                ['updated_at' => now()]
            );
    
            // Clear existing cart items
            $userCart->items()->delete();
    
            // Add the new cart items if provided
            if (!empty($cartData['cart']['items'])) {
                foreach ($cartData['cart']['items'] as $item) {
                    CartItem::create([
                        'cart_id' => $userCart->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
    
            $userCart->touch(); // Update the cart's `updated_at` timestamp
        } else {
            // Delete the user's cart if no cart data is sent
            $userCart = UserCart::where('user_id', $user->id)->first();
            if ($userCart) {
                $userCart->items()->delete();
                $userCart->delete();
            }
        }
    
        // Revoke the user's token
        $user->currentAccessToken()->delete();
    
        return response()->json([
            'message' => 'Logged out successfully',
        ], 200);
    }
    
    

    public function validateToken(Request $request)
{
    try {
        $user = $request->user();

        if ($user) {
            return response()->json([
                'status' => true,
                'user' => $user, // Return user details if token is valid
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Token is invalid'], 401);
    } catch (\Exception $e) {
        return response()->json(['status' => false, 'message' => 'Token is invalid or expired'], 401);
    }
}

}
