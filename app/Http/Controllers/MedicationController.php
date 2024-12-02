<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationNameResource;
use App\Http\Resources\MedicationResource;
use App\Models\MedicationName;
use App\Models\Medications;
use App\Models\Pet;
use App\Models\PetOwner;
use Carbon\Carbon;
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
                ->orWhere('remarks', 'like', '%' . $search . '%')
                ->orWhereHas('veterinarian', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%'); // Search veterinarian name
                });

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
    public function getTodayMedicationFeesForAll()
    {
        $today = Carbon::today();

        $totalFees = Medications::whereDate('medication_date', $today)
            ->sum('fee');

        return response()->json([
            'date' => $today->toDateString(),
            'total_fees' => $totalFees
        ]);
    }
    public function indexFeesChart(Request $request)
{
    $type = $request->query('type', 'monthly'); // "monthly" or "yearly"

    $currentDate = Carbon::now();
    $startOfThisYear = $currentDate->copy()->startOfYear();
    $endOfThisYear = $currentDate->copy()->endOfYear();

    $categories = [];
    $data = [];

    if ($type === 'yearly') {
        // Yearly data generation
        $yearlyStats = collect();

        for ($year = 2023; $year <= $currentDate->year; $year++) { // Start explicitly from 2023
            $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
            $endOfYear = Carbon::create($year, 12, 31)->endOfYear();

            $yearlyFee = Medications::whereBetween('medication_date', [$startOfYear, $endOfYear])
                ->sum('fee');

            $yearlyStats->push([
                'year' => $year,
                'fee' => $yearlyFee,
            ]);
        }

        $categories = $yearlyStats->pluck('year');
        $data = $yearlyStats->pluck('fee');
    } else {
        // Monthly data generation
        $monthlyStats = collect();

        for ($i = 0; $i < 12; $i++) {
            $startOfMonth = $startOfThisYear->copy()->addMonths($i);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            if ($startOfMonth->gt($currentDate)) break;

            $monthlyFee = Medications::whereBetween('medication_date', [$startOfMonth, $endOfMonth])
                ->sum('fee');

            $monthlyStats->push([
                'month' => $startOfMonth->format('M'),
                'fee' => $monthlyFee,
            ]);
        }

        $categories = $monthlyStats->pluck('month');
        $data = $monthlyStats->pluck('fee');
    }

    $totalFees = Medications::whereBetween('medication_date', [$startOfThisYear, $endOfThisYear])->sum('fee');

    return response()->json([
        'categories' => $categories,
        'data' => $data,
        'total_fees' => $totalFees,
    ]);
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