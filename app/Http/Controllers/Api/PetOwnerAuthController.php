<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PetOwner;
// use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PetOwnerAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:pet_owners,email',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:10240',
            'password' => ['required', 'confirmed'],
            'addr_zone' => 'required|string|max:50',
            'addr_brgy' => 'required|string|max:50',
            'phone_number' => 'required|string|max:11',
        ]);

        $pet_owner = PetOwner::create([
            'name' => $request->name,
            'email' => $request->email,
            'image' => $request->image,
            'password' => Hash::make($request->password),
            'addr_zone' => $request->addr_zone,
            'addr_brgy' => $request->addr_brgy,
            'phone_number' => $request->phone_number,
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'petowner' . '.' . $getfileExtension;
            $file->storeAs('public/petowners_profile', $createnewFileName);

            $pet_owner->image = $createnewFileName;
            $pet_owner->save();
        }

        return response()->json([
            'message' => 'Pet Owner registered successfully!',
            'pet_owner' => $pet_owner,
            'api_token' => $pet_owner->createToken("Pet Owners")->plainTextToken
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);

        if(!Auth::guard('petowner')->attempt($request->only(['email', 'password']))){
            return response()->json([
                'message' => 'Email and Password invalid!',
            ], 401);
        }

        $pet_owner = PetOwner::where('email', $request->email)->first();

        return response()->json([
            'message' => 'Pet Owner Logged In successfully!',
            'pet_owner' => $pet_owner,
            'api_token' => $pet_owner->createToken("API TOKEN")->plainTextToken
        ], 201);

    }


    public function profile()
    {
        try {
            if (Auth::check()) {
                $pet_owner = Auth::user();

                return response()->json([
                    'message' => 'Profile Information',
                    'pet_owner' => $pet_owner
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pet Owner not authenticated'
                ], 401);
            }
        } catch (\Exception $e) {
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


    public function updateDetails(Request $request, PetOwner $petowner)
    {
        // Validate the request and update the pet owner's details
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif|max:10240',
            'addr_zone' => 'required|string|max:50',
            'addr_brgy' => 'required|string|max:50',
            'phone_number' => 'required|string|max:11',
        ]);

        $petowner->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Details updated successfully',
            'petowner' => $petowner,
        ], 200);
    }

}