<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // Retrieve the authenticated user's profile
    public function profile(Request $request)
    {
         if($request->user()){
            return response()->json([
                'message'=>'user',
                'user'=> $request->user()
            ],200);
         }
         else{
            return response()->json([
                'message'=>'no user found',
                
            ],404);
         }

     
    }

    // Update the authenticated user's profile
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'image' => 'sometimes|image|max:2048',
        ]);
    
        // Ensure the authenticated user exists
        if (!$user instanceof User) {
            $user = User::find($user->id); // Refetch the user model if not resolved
        }
    
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($user->image_url) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->image_url));
            }
    
            // Save new image
            $path = $request->file('image')->store('profile_images', 'public');
            $validatedData['image_url'] = '/storage/' . $path;
        }
    
        $user->update($validatedData);
    
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }
    

    // Update the authenticated user's password
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!$user instanceof User) {
            $user = User::find($user->id); // Refetch the user model if not resolved
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }
}
