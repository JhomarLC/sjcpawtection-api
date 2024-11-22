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

        public function indexchart(Request $request)
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

            // Define start and end of the current year
            $startOfThisYear = Carbon::now()->startOfYear()->toDateString(); // January 1st of this year
            $endOfThisYear = Carbon::now()->endOfYear()->toDateString();     // December 31st of this year

            // Calculate total pets with the specific medication
            $totalMedicationPetsQuery = Pet::query()
                ->where(function ($query) use ($pettype) {
                    if ($pettype !== 'All') {
                        $query->where('pet_type', $pettype); // Filter by pet type
                    }
                })
                ->whereHas('medications', function ($query) use ($startOfThisYear, $endOfThisYear){
                    $query->where('medication_name_id', 1)
                    ->whereBetween('medication_date', [$startOfThisYear, $endOfThisYear]); // Filter pets with specific medication
                })
                ->whereHas('petowner', function ($query) use ($filter, $count) {
                    if (!empty($filter)) {
                        $query->where('addr_brgy', 'like', '%' . $filter . '%');
                    }
                    if (!empty($count) && $count !== 'All') {
                        $query->where('addr_brgy', $count);
                    }
                });

            $totalCount = $totalMedicationPetsQuery->count();

            // Get date range for monthly or yearly statistics
            $firstRecord = Pet::whereHas('medications', function ($query) {
                $query->where('medication_name_id', 1);
            })->orderBy('created_at', 'asc')->first();

            if (!$firstRecord) {
                return response()->json([
                    'categories' => [],
                    'data' => [],
                    'total_count' => $totalCount,
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

                    logger("Start of year: " . $startOfYear->toDateString());
                    logger("End of year: " . $endOfYear->toDateString());

                    $yearlyCount = Pet::query()
                        ->where(function ($query) use ($pettype) {
                            if ($pettype !== 'All') {
                                $query->where('pet_type', $pettype); // Filter by pet type
                            }
                        })
                        ->whereHas('medications', function ($query) use ($startOfYear, $endOfYear) {
                            $query->where('medication_name_id', 1)
                                    ->whereBetween('medication_date', [$startOfYear, $endOfYear]);
                        })
                        ->whereHas('petowner', function ($query) use ($filter, $count) {
                            if (!empty($filter)) {
                                $query->where('addr_brgy', 'like', '%' . $filter . '%');
                            }
                            if (!empty($count) && $count !== 'All') {
                                $query->where('addr_brgy', $count);
                            }
                        })
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
                    'total_count' => $totalCount,
                ]);
            } else {
                // Monthly stats
                $monthlyStats = collect([]);

                for ($i = 0; $i < 12; $i++) {
                    $startOfMonth = $currentDate->copy()->startOfYear()->addMonths($i);
                    $endOfMonth = $startOfMonth->copy()->endOfMonth();

                    if ($startOfMonth->gt($currentDate)) break;

                    $monthlyCount = Pet::query()
                        ->where(function ($query) use ($pettype) {
                            if ($pettype !== 'All') {
                                $query->where('pet_type', $pettype); // Filter by pet type
                            }
                        })
                        ->whereHas('medications', function ($query) use ($startOfMonth, $endOfMonth){
                            $query->where('medication_name_id', 1)
                                ->whereBetween('medication_date', [$startOfMonth, $endOfMonth]);
                        })
                        ->whereHas('petowner', function ($query) use ($filter, $count) {
                            if (!empty($filter)) {
                                $query->where('addr_brgy', 'like', '%' . $filter . '%');
                            }
                            if (!empty($count) && $count !== 'All') {
                                $query->where('addr_brgy', $count);
                            }
                        })
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
                    'total_count' => $totalCount,
                ]);
            }
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



    // public function indexchart2(Request $request)
    // {
    //     $search = $request->query('search');
    //     $filter = $request->query('filter'); // Filter by address barangay
    //     $count = $request->query('count', 'All'); // "All" or specific barangay
    //     $type = $request->query('type', 'monthly'); // "monthly" or "yearly"
    //     $pettype = $request->query('pettype', 'All'); // "All", "Dog" or "Cat"

    //     $petOwnerQuery = PetOwner::query();

    //     // Apply address filter if provided
    //     if (!empty($filter)) {
    //         $petOwnerQuery->where('addr_brgy', 'like', '%' . $filter . '%');
    //     }

    //     // Search for owners if search is provided
    //     if (!empty($search)) {
    //         $petOwnerQuery->where(function ($query) use ($search) {
    //             $query->where('name', 'like', '%' . $search . '%')
    //                 ->orWhere('email', 'like', '%' . $search . '%');
    //         });
    //     }

    //     // Define start and end of the current year
    //     $startOfThisYear = Carbon::now()->startOfYear()->toDateString(); // January 1st of this year
    //     $endOfThisYear = Carbon::now()->endOfYear()->toDateString();     // December 31st of this year

    //     // Calculate total pets with the specific medication
    //     $totalMedicationPetsQuery = Pet::query()
    //         ->where(function ($query) use ($pettype) {
    //             if ($pettype !== 'All') {
    //                 $query->where('pet_type', $pettype); // Filter by pet type
    //             }
    //         })
    //         ->whereHas('petowner', function ($query) use ($filter, $count) {
    //             if (!empty($filter)) {
    //                 $query->where('addr_brgy', 'like', '%' . $filter . '%');
    //             }
    //             if (!empty($count) && $count !== 'All') {
    //                 $query->where('addr_brgy', $count);
    //             }
    //         });

    //     $totalCount = $totalMedicationPetsQuery->count();

    //     // Get date range for monthly or yearly statistics
    //     $firstRecord = Pet::where('status', 'approved')->orderBy('created_at', 'asc')->first();

    //     if (!$firstRecord) {
    //         return response()->json([
    //             'categories' => [],
    //             'data' => [],
    //             'total_count' => $totalCount,
    //         ]);
    //     }

    //     $firstDate = Carbon::parse($firstRecord->created_at)->subYear();
    //     $currentDate = Carbon::now();

    //     if ($type === 'yearly') {
    //         // Yearly stats
    //         $yearlyStats = collect([]);

    //         for ($year = $firstDate->year; $year <= $currentDate->year; $year++) {
    //             $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
    //             $endOfYear = Carbon::createFromDate($year, 1, 1)->endOfYear();

    //             logger("Start of year: " . $startOfYear->toDateString());
    //             logger("End of year: " . $endOfYear->toDateString());

    //             $yearlyCount = Pet::query()
    //                 ->where(function ($query) use ($pettype) {
    //                     if ($pettype !== 'All') {
    //                         $query->where('pet_type', $pettype); // Filter by pet type
    //                     }
    //                 })
    //                 ->whereHas('medications', function ($query) use ($startOfYear, $endOfYear) {
    //                     $query->where('medication_name_id', 1)
    //                             ->whereBetween('medication_date', [$startOfYear, $endOfYear]);
    //                 })
    //                 ->whereHas('petowner', function ($query) use ($filter, $count) {
    //                     if (!empty($filter)) {
    //                         $query->where('addr_brgy', 'like', '%' . $filter . '%');
    //                     }
    //                     if (!empty($count) && $count !== 'All') {
    //                         $query->where('addr_brgy', $count);
    //                     }
    //                 })
    //                 ->count();

    //             $yearlyStats->push([
    //                 'year' => $year,
    //                 'count' => $yearlyCount,
    //             ]);
    //         }

    //         $categories = $yearlyStats->pluck('year');
    //         $data = $yearlyStats->pluck('count');

    //         return response()->json([
    //             'categories' => $categories,
    //             'data' => $data,
    //             'total_count' => $totalCount,
    //         ]);
    //     } else {
    //         // Monthly stats
    //         $monthlyStats = collect([]);

    //         for ($i = 0; $i < 12; $i++) {
    //             $startOfMonth = $currentDate->copy()->startOfYear()->addMonths($i);
    //             $endOfMonth = $startOfMonth->copy()->endOfMonth();

    //             if ($startOfMonth->gt($currentDate)) break;

    //             $monthlyCount = Pet::query()
    //                 ->where(function ($query) use ($pettype) {
    //                     if ($pettype !== 'All') {
    //                         $query->where('pet_type', $pettype); // Filter by pet type
    //                     }
    //                 })
    //                 ->whereHas('medications', function ($query) use ($startOfMonth, $endOfMonth){
    //                     $query->where('medication_name_id', 1)
    //                         ->whereBetween('medication_date', [$startOfMonth, $endOfMonth]);
    //                 })
    //                 ->whereHas('petowner', function ($query) use ($filter, $count) {
    //                     if (!empty($filter)) {
    //                         $query->where('addr_brgy', 'like', '%' . $filter . '%');
    //                     }
    //                     if (!empty($count) && $count !== 'All') {
    //                         $query->where('addr_brgy', $count);
    //                     }
    //                 })
    //                 ->count();

    //             $monthlyStats->push([
    //                 'month' => $startOfMonth->format('M'),
    //                 'count' => $monthlyCount,
    //             ]);
    //         }

    //         $categories = $monthlyStats->pluck('month');
    //         $data = $monthlyStats->pluck('count');

    //         return response()->json([
    //             'categories' => $categories,
    //             'data' => $data,
    //             'total_count' => $totalCount,
    //         ]);
    //     }
    // }
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