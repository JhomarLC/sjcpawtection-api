<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Veterinarians;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class VeterinarianAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:veterinarians,email',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'password' => ['required', 'confirmed'],
            'position' => 'required|string|max:100',
            'license_number' => 'required|string|max:50',
            'phone_number' => 'required|string|max:11',
            'electronic_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $veterinarian = Veterinarians::create([
            'name' => $request->name,
            'email' => $request->email,
            'image' => $request->image,
            'password' => Hash::make($request->password),
            'position' => $request->position,
            'license_number' => $request->license_number,
            'phone_number' => $request->phone_number,
            'electronic_signature' => $request->electronic_signature,
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'vet' . '.' . $getfileExtension;
            $file->storeAs('public/vet_profiles', $createnewFileName);

            $veterinarian->image = $createnewFileName;
            $veterinarian->save();
        }
        if ($request->hasFile('electronic_signature')) {
            $esfile = $request->file('electronic_signature');
            $esgetfileExtension = $esfile->getClientOriginalExtension();
            $escreatenewFileName = time() . '_' . 'esignature' . '.' . $esgetfileExtension;
            $esfile->storeAs('public/electronic_signatures', $escreatenewFileName);

            $veterinarian->electronic_signature = $escreatenewFileName;
            $veterinarian->save();
        }
        return response()->json([
            'message' => 'Veterinarians registered successfully!',
            'user' => $veterinarian,
            'api_token' => $veterinarian->createToken("Api Token")->plainTextToken
        ], 201);
    }

    // public function login(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|string|email|max:255',
    //         'password' => 'required',
    //     ]);

    //     if(!Auth::guard('vet')->attempt($request->only(['email', 'password']))){
    //         return response()->json([
    //             'message' => 'Email and Password invalid!',
    //         ], 401);
    //     }

    //     $veterinarian = Veterinarians::where('email', $request->email)->first();

    //     return response()->json([
    //         'message' => 'Veterinarian Logged In successfully!',
    //         'user' => $veterinarian,
    //         'api_token' => $veterinarian->createToken("API TOKEN")->plainTextToken
    //     ], 201);

    // }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required',
        ]);

        if (!Auth::guard('vet')->attempt($request->only(['email', 'password']))) {
            return response()->json([
                'message' => 'Email and Password invalid!',
            ], 401);
        }

        $veterinarian = Veterinarians::where('email', $request->email)->first();

        // Check if the veterinarian's status is approved
        if ($veterinarian->status !== 'approved') {
            return response()->json([
                'message' => 'Your account is pending and requires administrator approval.',
            ], 403);
        }

        return response()->json([
            'message' => 'Veterinarian Logged In successfully!',
            'user' => $veterinarian,
            'api_token' => $veterinarian->createToken("API TOKEN")->plainTextToken
        ], 201);
    }

    public function profile()
    {
        try {
            if (Auth::check()) {
                $veterinarian = Auth::user();

                return response()->json([
                    'message' => 'Profile Information',
                    'user' => $veterinarian
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
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


}