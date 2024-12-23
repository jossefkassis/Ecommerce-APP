<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    // Add a product to favorites
    public function store($productId)
    {
      

        $user = Auth::user();
        
        if (!$user instanceof User) {
            $user = User::find($user->id); // Refetch the user model if not resolved
        }

        if ($user->favorites()->where('product_id', $productId)->exists()) {
            return response()->json(['message' => 'Product is already in favorites'], 400);
        }

        // Add to favorites
        $favorite = Favorite::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json(['message' => 'Product added to favorites', 'favorite' => $favorite], 200);
    }

    // Remove a product from favorites
    public function destroy($productId)
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            $user = User::find($user->id); // Refetch the user model if not resolved
        }

        $favorite = $user->favorites()->where('product_id', $productId)->first();

        if (!$favorite) {
            return response()->json(['message' => 'Product not found in favorites'], 404);
        }

        $favorite->delete();

        return response()->json(['message' => 'Product removed from favorites'], 200);
    }



    // List all favorites for the authenticated user
    public function index()
    {
        $user = Auth::user();

         if (!$user instanceof User) {
            $user = User::find($user->id); // Refetch the user model if not resolved
        }

        $favorites = $user->favorites()->with('product')->get();

        return response()->json(['favorites' => $favorites], 200);
    }
}
