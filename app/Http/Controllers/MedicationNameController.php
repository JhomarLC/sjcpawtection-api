<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationNameResource;
use App\Models\MedicationName;
use App\Models\Medications;
use App\Models\MedicationType;
use Illuminate\Http\Request;

class MedicationNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, MedicationType $medtype)
    {
        $search = $request->query('search');
        // Start a query on the mednames relationship
        $query = $medtype->mednames();


        if (!empty($search)) {
            $query->where(function($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('status', $search);
            });
        }

        // Check if 'status' query parameter is present and not empty
        if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
            $query->where('status', $request->status);
        }


        // Get the filtered or unfiltered results
        $mednames = $query->latest()->get();

        $mednames->load('medtype');
        return MedicationNameResource::collection($mednames);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, MedicationType $medtype)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required'
        ]);

        $medicationName = $medtype->mednames()->create([...$validatedData, 'medication_type_id' => $medtype->id]);
        return new MedicationNameResource($medicationName);
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicationType $medtype, MedicationName $medname)
    {
        if($medtype->id !== $medname->medication_type_id){
            return response()->json(['error' => 'You are unauthorized to make an update'], 404);
        }
        return new MedicationNameResource($medname);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MedicationType $medtype, MedicationName $medname)
    {
        if($medtype->id !== $medname->medication_type_id){
            return response()->json(['error' => 'You are unauthorized to make an update'], 404);
        }
        $validatedData = $request->validate([
            'name' => 'required|string|max:100',
            'status' => 'required'
        ]);

        $medname->update([
            ...$validatedData
        ]);

        return response()->json([
            'message' => 'Medication Name successfully updated :)',
            'mname' => $medname
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function archive(Request $request, MedicationType $medtype, MedicationName $medname)
    {
        if($medtype->id !== $medname->medication_type_id){
            return response()->json(['error' => 'You are unauthorized to make an update'], 404);
        }

        $medname->update([
            'status' => 'archive'
        ]);

        return response()->json([
            'message' => 'Medication Name successfully archived :)',
            'medication_name' => $medname
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function unarchive(Request $request, MedicationType $medtype, MedicationName $medname)
    {
        if($medtype->id !== $medname->medication_type_id){
            return response()->json(['error' => 'You are unauthorized to make an update'], 404);
        }

        $medname->update([
            'status' => 'unarchived'
        ]);

        return response()->json([
            'message' => 'Medication Name successfully unarchived :)',
            'medication_name' => $medname
        ], 200);
    }

     /**
     * Remove the specified resource from storage.
     */
    public function destroy(MedicationName $medname)
    {
        $medname->delete();
        return response()->json(['message' => 'Medication name deleted successfully'], 200);
    }

}