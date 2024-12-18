<?php

namespace App\Http\Controllers;

use App\Http\Resources\PetPhotosResource;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Models\PetOwner;
use App\Models\PetPhotos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PetOwner $petowner)
    {
        $search = $request->query('search');

        // Retrieve the pets associated with the pet owner
        $pets = $petowner->pets()->latest();

        // Apply search filter if provided
        if (!empty($search)) {
            $pets->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('status', $search); // Ensure this stays scoped
            });
        }

        // Return the pet resources as a collection
        return PetResource::collection(
            $pets->get()
        );
    }

    public function petsWithMedication(Request $request, PetOwner $petowner)
    {
          // Load all pets with their medications, filtering for upcoming next_vaccination dates
        $petsWithMedications = $petowner->load([
            'pets.medications' => function ($query) {
                $query->where('next_vaccination', '>=', Carbon::now()->toDateString())
                    ->orderBy('next_vaccination', 'asc');
            },
            'pets.medications.medicationname.medtype',
            'pets.medications.veterinarian',
        ]);

        // Transform or return the data as a JSON response
        return response()->json([
            'pet_owner' => $petowner->name,
            'pets' => $petsWithMedications->pets->map(function ($pet) {
                return [
                    'name' => $pet->name,
                    'breed' => $pet->breed,
                    'petype' => $pet->pet_type,
                    'gender' => $pet->gender,
                    'medications' => $pet->medications->map(function ($medication) {
                        return [
                            'medication_name' => $medication->medicationname->name ?? 'N/A',
                            'medication_type' => $medication->medicationname->medtype->name ?? 'N/A',
                            'veterinarian' => $medication->veterinarian->name ?? 'N/A',
                            'batch_number' => $medication->batch_number,
                            'next_vaccination' => $medication->next_vaccination,
                            'medication_date' => $medication->medication_date,
                            'remarks' => $medication->remarks,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function getNextVaccination(Request $request, Pet $pet)
    {
        $pet_medications = $pet->medications()
            ->with('medicationname.medtype')
            ->where('next_vaccination', '>=', Carbon::now()->toDateString()) // Filter for upcoming vaccinations
            ->orderBy('next_vaccination', 'asc') // Sort by date for clarity
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $pet_medications
        ]);
    }

    public function indexchart(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter'); // Filter by barangay
        $count = $request->query('count', 'All'); // "All" or specific barangay
        $type = $request->query('type', 'monthly'); // "monthly" or "yearly"
        $pettype = $request->query('pettype', 'All'); // "All", "Dog" or "Cat"
        $medication_id = $request->query('medication', 1);

        $startOfThisYear = Carbon::now()->startOfYear();
        $endOfThisYear = Carbon::now()->endOfYear();

        // Helper function to apply filters
        $applyFilters = function ($query) use ($filter, $count, $pettype) {
            if ($pettype !== 'All') {
                $query->where('pet_type', $pettype);
            }
            $query->whereHas('petowner', function ($query) use ($filter, $count) {
                if (!empty($filter)) {
                    $query->where('addr_brgy', 'like', '%' . $filter . '%');
                }
                if (!empty($count) && $count !== 'All') {
                    $query->where('addr_brgy', $count);
                }
            });
        };

        // Total pets with the specific medication
        $petsWithMedicationQuery = Pet::query();
        $applyFilters($petsWithMedicationQuery);
        $petsWithMedicationQuery->whereHas('medications', function ($query) use ($medication_id, $startOfThisYear, $endOfThisYear) {
            $query->where('medication_name_id', $medication_id)
                ->whereBetween('medication_date', [$startOfThisYear, $endOfThisYear]);
        });
        $totalCount = $petsWithMedicationQuery->count();

        // Total pets regardless of medication
        $totalPetsQuery = Pet::query()
        ->where('status', 'approved');
        $applyFilters($totalPetsQuery);
        $totalPetCount = $totalPetsQuery->count();

        // If no data, return early
        if ($totalPetCount === 0) {
            return response()->json([
                'categories' => [],
                'data' => [],
                'total_count' => $totalCount,
                'total_pet_count' => $totalPetCount,
            ]);
        }

        // Get date range for chart (Yearly or Monthly)
        $firstRecord = Pet::query()
        ->where('status', 'approved')
        ->orderBy('created_at', 'asc')
        ->first();

        if (!$firstRecord) {
            return response()->json([
                'categories' => [],
                'data' => [],
                'total_count' => $totalCount,
                'total_pet_count' => $totalPetCount,
            ]);
        }

        $firstDate = Carbon::create(2023, 1, 1)->startOfYear(); // Start from 2023
        $currentDate = Carbon::now();

        $categories = [];
        $data = [];

        if ($type === 'yearly') {
            // Yearly data generation
            $yearlyStats = collect();

            for ($year = 2023; $year <= $currentDate->year; $year++) { // Start explicitly from 2023
                $startOfYear = Carbon::create($year, 1, 1)->startOfYear();
                $endOfYear = Carbon::create($year, 1, 1)->endOfYear();

                $yearlyCount = Pet::query();
                $applyFilters($yearlyCount);
                $yearlyCount->whereHas('medications', function ($query) use ($medication_id, $startOfYear, $endOfYear) {
                    $query->where('medication_name_id', $medication_id)
                        ->whereBetween('medication_date', [$startOfYear, $endOfYear]);
                });

                $yearlyStats->push([
                    'year' => $year,
                    'count' => $yearlyCount->count(),
                ]);
            }

            $categories = $yearlyStats->pluck('year');
            $data = $yearlyStats->pluck('count');
        } else {
            // Monthly data generation
            $monthlyStats = collect();

            for ($i = 0; $i < 12; $i++) {
                $startOfMonth = $currentDate->copy()->startOfYear()->addMonths($i);
                $endOfMonth = $startOfMonth->copy()->endOfMonth();

                if ($startOfMonth->gt($currentDate)) break;

                $monthlyCount = Pet::query();
                $applyFilters($monthlyCount);
                $monthlyCount->whereHas('medications', function ($query) use ($medication_id, $startOfMonth, $endOfMonth) {
                    $query->where('medication_name_id', $medication_id)
                        ->whereBetween('medication_date', [$startOfMonth, $endOfMonth]);
                });

                $monthlyStats->push([
                    'month' => $startOfMonth->format('M'),
                    'count' => $monthlyCount->count(),
                ]);
            }

            $categories = $monthlyStats->pluck('month');
            $data = $monthlyStats->pluck('count');
        }

        return response()->json([
            'categories' => $categories,
            'data' => $data,
            'total_count' => $totalCount,
            'total_pet_count' => $totalPetCount,
            'percentage'=> $totalCount / $totalPetCount * 100,
        ]);
    }

   public function indexchart2(Request $request)
    {
    $search = $request->query('search');
    $filter = $request->query('filter'); // Filter by address barangay
    $count = $request->query('count', 'All'); // "All" or specific barangay
    $type = $request->query('type', 'monthly'); // "monthly" or "yearly"
    $pettype = $request->query('pettype', 'All'); // "All", "Dog" or "Cat"

    $petOwnerQuery = PetOwner::query();

    // Apply address filter if provided
    if (!empty($filter)) {
        $petOwnerQuery->where('addr_brgy', 'like', '%' . $filter . '%');
    }

    // Search for owners if search is provided
    if (!empty($search)) {
        $petOwnerQuery->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        });
    }

    // Get date range for monthly or yearly statistics
    $firstRecord = Pet::query()
        ->where('status', 'approved')
        ->orderBy('created_at', 'asc')
        ->first();

    if (!$firstRecord) {
        return response()->json([
            'categories' => [],
            'data' => [],
            'total_count' => 0,
        ]);
    }

    $firstDate = Carbon::parse($firstRecord->created_at)->subYear();
    $currentDate = Carbon::now();

    if ($type === 'yearly') {
        // Yearly stats
        $yearlyStats = collect([]);

        for ($year = $firstDate->year; $year <= $currentDate->year; $year++) {
            $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endOfYear = Carbon::createFromDate($year, 1, 1)->endOfYear();

            $yearlyCount = Pet::query()
                ->where('status', 'approved')
                ->where(function ($query) use ($pettype) {
                    if ($pettype !== 'All') {
                        $query->where('pet_type', $pettype); // Filter by pet type
                    }
                })
                ->whereHas('petowner', function ($query) use ($filter, $count) {
                    if (!empty($filter)) {
                        $query->where('addr_brgy', 'like', '%' . $filter . '%');
                    }
                    if (!empty($count) && $count !== 'All') {
                        $query->where('addr_brgy', $count);
                    }
                })
                ->whereBetween('created_at', [$startOfYear, $endOfYear])
                ->count();

            $yearlyStats->push([
                'year' => $year,
                'count' => $yearlyCount,
            ]);
        }

        $categories = $yearlyStats->pluck('year');
        $data = $yearlyStats->pluck('count');

        return response()->json([
            'categories' => $categories,
            'data' => $data,
            'total_count' => $yearlyStats->sum('count'),
        ]);
    } else {
        // Monthly stats
        $monthlyStats = collect([]);

        for ($i = 0; $i < 12; $i++) {
            $startOfMonth = $currentDate->copy()->startOfYear()->addMonths($i);
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            if ($startOfMonth->gt($currentDate)) break;

            $monthlyCount = Pet::query()
                ->where('status', 'approved')
                ->where(function ($query) use ($pettype) {
                    if ($pettype !== 'All') {
                        $query->where('pet_type', $pettype); // Filter by pet type
                    }
                })
                ->whereHas('petowner', function ($query) use ($filter, $count) {
                    if (!empty($filter)) {
                        $query->where('addr_brgy', 'like', '%' . $filter . '%');
                    }
                    if (!empty($count) && $count !== 'All') {
                        $query->where('addr_brgy', $count);
                    }
                })
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            $monthlyStats->push([
                'month' => $startOfMonth->format('M'),
                'count' => $monthlyCount,
            ]);
        }

        $categories = $monthlyStats->pluck('month');
        $data = $monthlyStats->pluck('count');

        return response()->json([
            'categories' => $categories,
            'data' => $data,
            'total_count' => $monthlyStats->sum('count'),
        ]);
    }
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

    public function updateWeightAndSize(Request $request, PetOwner $petowner, $petId)
    {
        // Validate the request
        $validatedData = $request->validate([
            'size' => 'required|string|max:100',
            'weight' => 'required|numeric',
        ]);

        // Find the pet belonging to the specified pet owner
        $pet = $petowner->pets()->findOrFail($petId);

        // Update only the size and weight attributes
        $pet->update([
            'size' => $validatedData['size'],
            'weight' => $validatedData['weight'],
        ]);

        // Reload the relationship to ensure updated data is returned
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