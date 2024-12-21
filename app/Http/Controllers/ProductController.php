<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Public: Show a specific active product by ID
    public function show($id)
    {
        $product = Product::with('gallery', 'shop:id,name', 'category:id,title')
            ->where('is_active', true)
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found or inactive'], 404);
        }

        return response()->json($product);
    }

    // Admin: Show a specific product by ID regardless of active status
    public function adminShow($id)
    {
        $product = Product::with('gallery', 'shop:id,name', 'category:id,title')->find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    // Public: Show all active products
    public function index()
    {
        $products = Product::with('shop:id,name', 'category:id,title')
            ->where('is_active', true)
            ->select('id', 'title', 'subtitle', 'description', 'featured_image', 'price', 'discount_price', 'quantity', 'in_stock', 'shop_id', 'category_id')
            ->get();

        return response()->json($products);
    }

    // Admin: Show all products regardless of active status
    public function adminIndex()
    {
        $products = Product::with('shop:id,name', 'category:id,title')->get();

        return response()->json($products);
    }

    // Public: Show all active products by shop ID
    public function getProductsByShop($shopId)
    {
        $products = Product::with('shop:id,name', 'category:id,title')
            ->where('shop_id', $shopId)
            ->where('is_active', true)
            ->select('id', 'title', 'subtitle', 'description', 'featured_image', 'price', 'discount_price', 'quantity', 'in_stock', 'shop_id', 'category_id')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for this shop'], 404);
        }

        return response()->json($products);
    }

    // Public: Show all active products by category ID
    public function getProductsByCategory($categoryId)
    {
        $products = Product::with('shop:id,name', 'category:id,title')
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->select('id', 'title', 'subtitle', 'description', 'featured_image', 'price', 'discount_price', 'quantity', 'in_stock', 'shop_id', 'category_id')
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products found for this category'], 404);
        }

        return response()->json($products);
    }

    // Admin: Create a new product
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id',
            'featured_image' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'gallery' => 'nullable|array',
            'gallery.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Default `is_active` to true if not provided
        $validatedData['is_active'] = $validatedData['is_active'] ?? true;

        // Determine `in_stock` based on quantity
        $validatedData['in_stock'] = $validatedData['quantity'] > 0;

        // Store the featured image
        $featuredPath = $request->file('featured_image')->store('products', 'public');
        $validatedData['featured_image'] = url('storage/' . $featuredPath);

        // Create the product
        $product = Product::create($validatedData);

        // Store gallery images
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $galleryImage) {
                $galleryPath = $galleryImage->store('gallery', 'public');
                ProductGallery::create([
                    'product_id' => $product->id,
                    'image_url' => url('storage/' . $galleryPath),
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product,
            'gallery' => $product->gallery,
        ], 201);
    }

    // Admin: Update a product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validatedData = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'shop_id' => 'nullable|exists:shops,id',
            'featured_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'gallery' => 'nullable|array',
            'gallery.*' => 'file|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Update featured image if provided
        if ($request->hasFile('featured_image')) {
            if ($product->featured_image) {
                $oldImagePath = str_replace(url('/storage/'), '', $product->featured_image);
                Storage::disk('public')->delete($oldImagePath);
            }

            $featuredPath = $request->file('featured_image')->store('products', 'public');
            $validatedData['featured_image'] = url('storage/' . $featuredPath);
        }

        // Update in_stock based on quantity
        if (isset($validatedData['quantity'])) {
            $validatedData['in_stock'] = $validatedData['quantity'] > 0;
        }

        // Update product
        $product->update($validatedData);

        // Update gallery images if provided
        if ($request->hasFile('gallery')) {
            // Delete existing gallery images from storage and database
            foreach ($product->gallery as $galleryImage) {
                $oldGalleryPath = str_replace(url('/storage/'), '', $galleryImage->image_url);
                Storage::disk('public')->delete($oldGalleryPath);
                $galleryImage->delete();
            }

            // Store new gallery images
            foreach ($request->file('gallery') as $galleryImage) {
                $galleryPath = $galleryImage->store('gallery', 'public');
                ProductGallery::create([
                    'product_id' => $product->id,
                    'image_url' => url('storage/' . $galleryPath),
                ]);
            }
        }

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product,
            'gallery' => $product->gallery,
        ]);
    }
}
