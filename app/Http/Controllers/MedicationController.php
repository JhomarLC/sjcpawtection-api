<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationNameResource;
use App\Http\Resources\MedicationResource;
use App\Models\MedicationName;
use App\Models\Medications;
use App\Models\Pet;
use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
    public function index(Request $request, Pet $pet)
    {
        $search = $request->query('search');

        // Start building the medications query
        $medications = $pet->medications()->latest();

        // Apply search filter if provided
        if (!empty($search)) {
            $medications->where(function($query) use ($search) {
                $query->whereHas('medicationname', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere('remarks', 'like', '%' . $search . '%');
            });
        }

        // Eager load relationships
        $medications->with('pet', 'medicationname.medtype', 'veterinarian');

        // Retrieve the medications
        $medications = $medications->get();

        // Return the medication resources as a collection
        return MedicationResource::collection($medications);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Pet $pet)
    {

        $validatedData = $request->validate([
            'medication_name_id' => 'required|integer',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date',
            'medication_date' => 'required|date',
            'next_vaccination' => 'nullable|date',
            'remarks' => 'required|string|max:100',
            'or_number' => 'required|string|max:100',
            'fee' => 'nullable|integer',
        ]);

        $pet_medications = $pet->medications()->create([
            ...$validatedData,
            'pet_id' => $pet->id,
            'veterinarians_id' => Auth::user()->id
        ]);

        return new MedicationResource($pet_medications);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pet $pet, Medications $medication)
    {
        if ($pet->id !== $medication->pet_id) {
            return response()->json(['error' => 'Pet does not have specific medication'], 404);
        }
        $medication->load('pet', 'medicationname', 'veterinarian');

        return new MedicationNameResource($medication);
    }

}