<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'password' => ['required', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'image' => $request->image,
            'password' => Hash::make($request->password),
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'paws' . '.' . $getfileExtension;
            $file->storeAs('public/user_profiles', $createnewFileName);

            $user->image = $createnewFileName;
            $user->save();
        }
        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $user->createToken("Api Token")->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);

        if(!Auth::attempt($request->only(['email', 'password']))){
            return response()->json([
                'message' => 'Email and Password invalid!',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'message' => 'User Logged In successfully!',
            'user' => $user,
            'api_token' => $user->createToken("API TOKEN")->plainTextToken
        ], 201);

    }

    public function profile()
    {
        try {
            // Check if the user is authenticated
            if (Auth::check()) {
                $user = Auth::user();

                // Return profile information with a 200 OK status
                return response()->json([
                    'message' => 'Profile Information',
                    'user' => $user
                ], 200);
            } else {
                // Return error if the user is not authenticated
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
        } catch (\Exception $e) {
            // Return error if an exception occurs
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve profile information'
            ], 500);
        }
    }

    public function logout()
    {
        if (Auth::check()) {
            try {
                Auth::user()->currentAccessToken()->delete();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully logged out'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Logout failed'
                ], 500);
            }
        }

        return response()->json([
            'status' => 'error',
            'message' => 'User not authenticated'
        ], 401);
    }
}