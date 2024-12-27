<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    public function     createUser(Request $request)
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

    return response()->json([
        'status'=>true,
        'message' => 'Login successful',
        'user' => $user,
        'access_token' => $token,
        'token_type' => 'Bearer',
    ]);
}


     /**
     * Log out the authenticated user.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }
}
