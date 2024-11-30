<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'user', // Assign default role
        ]);

        $token = $user->createToken($user->name.'auth_token')->plainTextToken;

        return response()->json([
           'message'=> 'register in successful',
            'user'=> $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ],201);
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
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email',$credentials['email'])->first();

        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json([
                'message'=>'wrong credentials'
            ],401);
        }

        $token = $user->createToken($user->name.'auth_token')->plainTextToken;

        return response()->json([
            'message'=> 'login in successful',
            'user'=> $user,
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
