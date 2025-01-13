<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // Public: List all active categories
    public function index()
    {
        $categories = Category::where('is_active', true)->get();
        return response()->json($categories);
    }

    // Public: Show a specific active category by ID
    public function show($id)
    {
        $category = Category::where('id', $id)->where('is_active', true)->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found or inactive'], 404);
        }

        return response()->json($category);
    }

    // Admin: List all categories
    public function adminIndex()
    {
        $categories = Category::all();
        return response()->json($categories);
    }

    // Admin: Show a specific category by ID
    public function adminShow($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    // Admin: Create a new category
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255|unique:categories',
            'description' => 'required|string',
            'image' => 'sometimes|file|mimetypes:image/jpeg,image/png,image/gif,image/webp|max:2048',// Validate the image file
            'is_active' => 'required|boolean',
        ]);
        
        if ($request->hasFile('image')) {
            // Store the image in the storage/images directory
            $path = $request->file('image')->store('category', 'public');
            $validatedData['image_url'] = '/storage/' . $path;
        }
    
        $category = Category::create($validatedData);
    
        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }
    

    // Admin: Update a category
    public function update(Request $request, $id)
    {
        $category = Category::find($id);
    
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
    
        // Validate the form-data input
        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255|unique:categories,title,' . $id,
            'description' => 'sometimes|required|string',
            'is_active' => 'sometimes|boolean',
            'image' => 'sometimes|file|mimes:jpg,jpeg,png,gif,webp|max:2048', // Validate image file
        ]);
    
        // Handle the image upload if provided
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($category->image_url) {
                $oldImagePath = str_replace('/storage/', '', $category->image_url); // Extract relative path
                Storage::disk('public')->delete($oldImagePath);
            }
    
            // Store new image
            $path = $request->file('image')->store('category', 'public');
            $validatedData['image_url'] = '/storage/' . $path; // Save full URL
        }
    
        // Update category with validated data
        $category->update($validatedData);
    
        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ]);
    }
    
    


    // Admin: Delete a category
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
