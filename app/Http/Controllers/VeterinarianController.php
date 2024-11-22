<?php

namespace App\Http\Controllers;

use App\Http\Resources\VeterinariansResource;
use App\Models\Veterinarians;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VeterinarianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
   /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $count = $request->query('count');
        $statusFilter = $request->query('status'); // Parameter for status filtering

        // Base query for listing veterinarians
        $vetQuery = Veterinarians::query()->orderBy('created_at', 'desc');

        // Apply search filter
        if (!empty($search)) {
            $vetQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('license_number', 'like', '%' . $search . '%')
                    ->orWhere('status', $search);
            });
        }

        // Apply status filter
        if (!empty($statusFilter) && in_array($statusFilter, ['pending', 'approved', 'archived'])) {
            $vetQuery->where('status', $statusFilter);
        }

        // Get the filtered count
        $filteredCount = $vetQuery->count();

        // Get the filtered data with pagination
        $filteredData = $vetQuery->paginate();

        // Return both the filtered data and the count
        return response()->json([
            'data' => VeterinariansResource::collection($filteredData),
            'filtered_count' => $filteredCount,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Veterinarians $vet)
    {
        return new VeterinariansResource($vet);
    }


    public function update(Request $request, Veterinarians $vet)
    {
        // Validate the request, including the image
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:10240',
            'position' => 'required|string|max:50',
            'license_number' => 'required|string|max:50',
            'phone_number' => 'required|string|max:11',
        ]);

        // Handle image upload if there is a new image file
        if ($request->hasFile('image')) {
            // Delete the old image if it exists
            if ($vet->image && Storage::exists('public/vet_profiles/' . $vet->image)) {
                Storage::delete('public/vet_profiles/' . $vet->image);
            }

            // Upload the new image
            $file = $request->file('image');

            $getfileExtension = $file->getClientOriginalExtension();
            $createnewFileName = time() . '_' . 'vet' . '.' . $getfileExtension;
            $file->storeAs('public/vet_profiles', $createnewFileName);

            // Update the image field in the validated data
            $validatedData['image'] = $createnewFileName;
        }

        // Update the PetOwner details with the validated data
        $vet->update($validatedData);

        // Return a success response with the updated pet owner details
        return response()->json([
            'status' => 'success',
            'message' => 'Details updated successfully',
            'vet' => $vet,
        ], 200);
    }



    /**
     * Approve the specified resource.
     */
    public function approve(Veterinarians $vet)
    {
        $vet->update([
            'status' => 'approved'
        ]);

        return response()->json([
            'message' => 'Veterinarian approved successfully!',
            'veterinarian' => $vet
        ], 200);
    }

    /**
     * Decline the specified resource.
     */
    public function decline(Veterinarians $vet)
    {
        $vet->update([
            'status' => 'declined'
        ]);

        return response()->json([
            'message' => 'Veterinarian declined successfully!',
            'veterinarian' => $vet
        ], 200);
    }

    /**
     * Archive the specified resource.
     */
    public function archive(Veterinarians $vet)
    {
        $vet->update([
            'status' => 'archived'
        ]);

        return response()->json([
            'message' => 'Veterinarian archived successfully!',
            'veterinarian' => $vet
        ], 200);
    }

}
