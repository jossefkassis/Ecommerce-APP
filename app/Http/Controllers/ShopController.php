<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;

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
        $shop = Shop::where('id', $id)->first();

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
            'is_active' => 'required|boolean',
        ]);

        $shop = Shop::create($validatedData);

        return response()->json([
            'message' => 'Shop created successfully',
            'shop' => $shop,
        ], 201);
    }

    // Admin: Update an existing shop
    public function update(Request $request, $id)
    {
        $shop = Shop::find($id);
    
        if (!$shop) {
            return response()->json(['message' => 'Shop not found'], 404);
        }
    
        // Validate only the fields present in the request
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:shops,name,' . $id,
            'is_active' => 'sometimes|boolean',
        ]);
    
        // Update the shop with the validated data
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
}
