<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationNameResource;
use App\Http\Resources\MedicationResource;
use App\Models\MedicationName;
use App\Models\Medications;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Pet $pet)
    {
        $medications = $pet->medications()->latest()->get();

        if ($medications->count() === 0) {
            return response()->json([
                'error' => 'No medications found for this pet'
            ], 404);
        }
        $medications->load('pet', 'medicationname', 'veterinarian');

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