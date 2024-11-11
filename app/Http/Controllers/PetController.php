<?php

namespace App\Http\Controllers;

use App\Http\Resources\PetPhotosResource;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Models\PetOwner;
use App\Models\PetPhotos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(PetOwner $petowner)
    {
        $pets = $petowner->pets()->latest();

        return PetResource::collection(
            $pets->paginate()
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function getphotos(PetOwner $petowner, Pet $pet)
    {
        if ($pet->pet_owner_id !== $petowner->id) {
            return response()->json(['error' => 'Pet does not belong to this pet owner'], 404);
        }
        $pet_photos = $pet->petphotos()->orderBy('created_at')->latest();
        return PetPhotosResource::collection(
            $pet_photos->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, PetOwner $petowner)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'breed' => 'required|string|max:100',
            'color_description' => 'required|string|max:100',
            'size' => 'required|string|max:100',
            'weight' => 'required|numeric',
            'date_of_birth' => 'required|date',
            'pet_type' => 'nullable'
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'pet' . '.' . $getfileExtension;
            $file->storeAs('public/pet_profile', $createnewFileName);

            $validatedData['image'] = $createnewFileName;
        }

        $pet = $petowner->pets()->create([...$validatedData,  'pet_owner_id' => $petowner->id]);
        $pet->load('petowner');
        return new PetResource($pet);
    }

    /**
     * Display the specified resource.
     */
    public function show(PetOwner $petowner, Pet $pet)
    {
        if ($pet->pet_owner_id !== $petowner->id) {
            return response()->json(['error' => 'Pet does not belong to this pet owner'], 404);
        }

        return new PetResource($pet->load('petowner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function deceased(PetOwner $petowner, Pet $pet)
    {
        if ($pet->pet_owner_id !== $petowner->id) {
            return response()->json(['error' => 'You are unauthorized to make an update'], 404);
        }

        $pet->update([
            'status' => 'deceased'
        ]);

        return response()->json([
            'message' => 'Pet is now deceased :(',
            'pet' => $pet
        ], 200);
    }

    public function approve(Pet $pet)
    {
        $pet->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => `Pet Successfully Approved`,
            'pet' => $pet
        ], 200);
    }

    public function decline(Pet $pet)
    {
        $pet->update([
            'status' => 'declined'
        ]);

        return response()->json([
            'message' => `Pet Declined :(`,
            'pet' => $pet
        ], 200);
    }

    public function addphotos(Request $request, PetOwner $petowner, Pet $pet)
    {
        if (!$petowner) {
            return response()->json([
                'message' => 'Pet owner not found.'
            ], 404);
        }

        if (!$pet) {
            return response()->json([
                'message' => 'Pet not found.'
            ], 404);
        }

        $request->validate([
            'image' => 'nullable|array',
            'image.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $uploadedImages = [];

            foreach ($request->file('image') as $index => $imageFile) {
                $getfileExtension = $imageFile->getClientOriginalExtension();
                $createnewFileName = time() . '_petowner_' . $petowner->id . '_' . $index . '.' . $getfileExtension;
                $path = $imageFile->storeAs('public/pet_photos', $createnewFileName);

                if ($path) {
                    $petPhoto = new PetPhotos();
                    $petPhoto->pet_id = $pet->id;
                    $petPhoto->image = $createnewFileName;

                    $petPhoto->save();

                    $uploadedImages[] = $createnewFileName;
                } else {
                    Log::error("Failed to store file: " . $imageFile->getClientOriginalName());
                }
            }

            if (count($uploadedImages) > 0) {
                return response()->json([
                    'message' => 'Photos have been successfully uploaded.',
                    'uploaded_images' => $uploadedImages,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Files were not uploaded successfully.',
                ], 500);
            }
        } else {
            return response()->json([
                'message' => 'No files were uploaded.',
            ], 400);
        }
    }

}