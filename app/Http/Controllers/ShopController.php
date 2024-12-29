<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShopController extends Controller
{
    // Public: List all active shops
    public function index()
    {
        $shops = Shop::where('is_active', true)->get();
        return response()->json($shops);
    }

    // Public: Show a specific active shop by ID
    public function show($id)
    {
        $shop = Shop::where('id', $id)->where('is_active', true)->first();

        if (!$shop) {
            return response()->json(['message' => 'Shop not found or inactive'], 404);
        }

        return response()->json($shop);
    }

    // Admin: List all shops
    public function adminIndex()
    {
        $shops = Shop::all();
        return response()->json($shops);
    }

    // Admin: Show a specific shop by ID
    public function adminShow($id)
    {
        $shop = Shop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        return response()->json($shop);
    }

    // Admin: Create a new shop
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:shops',
            'description' => 'required|string',
            'is_active' => 'required|boolean',
            'image' => 'sometimes|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
        ]);
    
        if ($request->hasFile('image')) {
            // Store the file in the "images" directory within "storage/app/public"
            $path = $request->file('image')->store('shop', 'public');
        
            // Generate the public URL
            $validatedData['image_url'] = url('storage/' . $path);
        
         }
    
        // Create the shop record in the database
        $shop = Shop::create($validatedData);
    
        return response()->json([
            'message' => 'Shop created successfully',
            'shop' => $shop,
        ], 201);
    }
    

    // Admin: Update a shop
    public function update(Request $request, $id)
    {
        $shop = Shop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:shops,name,' . $id,
            'description' => 'sometimes|required|string',
            'image' => 'sometimes|file|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048', // Validate image
            'is_active' => 'sometimes|boolean',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($shop->image_url) {
                $oldImagePath = str_replace(url('/storage/'), '', $shop->image_url);
                Storage::disk('public')->delete($oldImagePath);
            }

            // Store the new image
            $path = $request->file('image')->store('shop', 'public');
            $validatedData['image_url'] = url(Storage::url($path));
        }

        $shop->update($validatedData);

        return response()->json([
            'message' => 'Shop updated successfully',
            'shop' => $shop,
        ]);
    }

    // Admin: Delete a shop
    public function destroy($id)
    {
        $shop = Shop::find($id);

        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }

        $shop->delete();

        return response()->json(['message' => 'Shop deleted successfully']);
    }
    public function shopsWithProducts()
    {
        $shops = Shop::with('products')->get();
    
        $shopsWithTotalSold = $shops->map(function ($shop) {
            $totalSold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'completed') // Only include completed orders
                ->where('products.shop_id', $shop->id)
                ->sum('order_items.quantity');
    
            $shop->total_sold = $totalSold;
            return $shop;
        });
    
        return response()->json($shopsWithTotalSold);
    }
    

}
