<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationTypeResource;
use App\Models\MedicationType;
use Illuminate\Http\Request;

class MedicationTypeController extends Controller
{
   /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Medicationtype::query();

        // Check if 'status' query parameter is present and not empty
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }

        // Paginate the filtered or unfiltered results
        $medicationTypes = $query->paginate(10);

        return MedicationTypeResource::collection($medicationTypes);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required'
        ]);

        $medicationType = MedicationType::create($validatedData);
        return new MedicationTypeResource($medicationType);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicationType $medtype)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required'
        ]);

        $medtype->update($validatedData);

        return new MedicationTypeResource($medtype);
    }

    public function show(MedicationType $medtype)
    {
        return new MedicationTypeResource($medtype);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicationType $medtype)
    {
        $medtype->delete();
        return response()->json(['message' => 'Medication type deleted successfully'], 200);
    }
}
