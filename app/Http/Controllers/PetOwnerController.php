<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetOwnerResource;
use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PetOwnerController extends Controller
{
    /**
    * Display a listing of the resource.
    */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter');

        $pet_owner_query = PetOwner::query();

        if ($filter) {
            $pet_owner_query->where('addr_brgy', 'like', '%' . $filter . '%');
        }

        if (!empty($search)) {
            $pet_owner_query->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        return PetOwnerResource::collection(
            $pet_owner_query->latest()->get(),
        );
    }


    /**
     * Display the specified resource.
     */
    public function show(PetOwner $petowner)
    {
        return new PetOwnerResource($petowner);
    }

    public function update(Request $request, PetOwner $petowner)
    {
        // Validate the request, including the image
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
            'addr_zone' => 'required|string|max:50',
            'addr_brgy' => 'required|string|max:50',
            'phone_number' => 'required|string|max:11',
        ]);

        // Handle image upload if there is a new image file
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($petowner->image && Storage::exists('public/petowners_profile/' . $petowner->image)) {
                Storage::delete('public/petowners_profile/' . $petowner->image);
            }

            // Upload the new image
            $file = $request->file('image');

            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'petowner' . '.' . $getfileExtension;
            $file->storeAs('public/petowners_profile', $createnewFileName);

            // Update the image field in the validated data
            $validatedData['image'] = $createnewFileName;
        }

        // Update the PetOwner details with the validated data
        $petowner->update($validatedData);

        // Return a success response with the updated pet owner details
        return response()->json([
            'status' => 'success',
            'message' => 'Details updated successfully',
            'petowner' => $petowner,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}