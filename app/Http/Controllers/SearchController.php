<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    // Public: Search for products
public function search(Request $request)
{
    $validatedData = $request->validate([
        'query' => 'nullable|string|max:255', // Search query is optional
        'shop_id' => 'nullable|exists:shops,id', // Validate shop_id if provided
        'category_id' => 'nullable|exists:categories,id', // Validate category_id if provided
    ]);

    $query = $validatedData['query'] ?? '';
    $shopId = $validatedData['shop_id'] ?? null;
    $categoryId = $validatedData['category_id'] ?? null;

    // Build the product query
    $productsQuery = Product::with('shop:id,name', 'category:id,title')
        ->where('is_active', true); // Only active products

    // Add search conditions for query
    if (!empty($query)) {
        $productsQuery->where(function ($q) use ($query) {
            $q->where('title', 'LIKE', "%$query%")
              ->orWhere('subtitle', 'LIKE', "%$query%")
              ->orWhere('description', 'LIKE', "%$query%");
        });
    }

    // Add filter for shop_id if provided
    if ($shopId) {
        $productsQuery->where('shop_id', $shopId);
    }

    // Add filter for category_id if provided
    if ($categoryId) {
        $productsQuery->where('category_id', $categoryId);
    }

    // Select the required columns and get the results
    $products = $productsQuery->select(
        'id',
        'title',
        'subtitle',
        'description',
        'featured_image',
        'price',
        'discount_price',
        'quantity',
        'in_stock',
        'shop_id',
        'category_id'
    )->get();

    if ($products->isEmpty()) {
        return response()->json(['message' => 'No products found'], 404);
    }

    return response()->json($products);
}


// Public: get categories and shops

public function getCategoriesAndShops(){
    $categories = Category::where('is_active', true)->get();
    $shops = Shop::where('is_active', true)->get();

    return response()->json(['status'=>200,'categories'=>$categories,'shops'=>$shops]);
}

}
