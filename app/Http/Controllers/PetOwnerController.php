<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PetOwnerResource;
use App\Models\PetOwner;
use Carbon\Carbon;
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
    // public function index(Request $request)
    // {
    //     $search = $request->query('search');
    //     $filter = $request->query('filter');
    //     $count = $request->query('count');

    //     $pet_owner_query = PetOwner::query();

    //     if ($filter) {
    //         $pet_owner_query->where('addr_brgy', 'like', '%' . $filter . '%');
    //     }

    //     if (!empty($search)) {
    //         $pet_owner_query->where(function($query) use ($search) {
    //             $query->where('name', 'like', '%' . $search . '%')
    //                 ->orWhere('email', 'like', '%' . $search . '%');
    //         });
    //     }

    //      // Variable to store the count result
    //      $filteredCount = null;

    //      // Implement the count logic if the 'count' parameter is provided
    //      if (!empty($count)) {
    //          $countQuery = $pet_owner_query;

    //          $brgy = [
    //             "A. Pascual",
    //             "Abar Ist",
    //             "Abar 2nd",
    //             "Bagong Sikat",
    //             "Caanawan",
    //             "Calaocan",
    //             "Camanacsacan",
    //             "Culaylay",
    //             "Dizol",
    //             "Kaliwanagan",
    //             "Kita-Kita",
    //             "Malasin",
    //             "Manicla",
    //             "Palestina",
    //             "Parang Mangga",
    //             "Villa Joson",
    //             "Pinili",
    //             "Rafael Rueda, Sr. Pob.",
    //             "Ferdinand E. Marcos Pob.",
    //             "Canuto Ramos Pob.",
    //             "Raymundo Eugenio Pob.",
    //             "Crisanto Sanchez Pob.",
    //             "Porais",
    //             "San Agustin",
    //             "San Juan",
    //             "San Mauricio",
    //             "Santo Niño 1st",
    //             "Santo Niño 2nd",
    //             "Santo Tomas",
    //             "Sibut",
    //             "Sinipit Bubon",
    //             "Santo Niño 3rd",
    //             "Tabulac",
    //             "Tayabo",
    //             "Tondod",
    //             "Tulat",
    //             "Villa Floresca",
    //             "Villa Marina"
    //          ];

    //          // Filter by the specified count type
    //          if (in_array($count, ['All', ...$brgy])) {
    //              $countQuery->where('status', $count);
    //          }

    //          // Get the count
    //          $filteredCount = $countQuery->count();

    //          // Return the paginated results as a resource collection, along with the filtered count if applicable
    //          return response()->json([
    //              'filtered_count' => $filteredCount, // Null if no count query is provided
    //          ]);
    //      }
    //     return PetOwnerResource::collection(
    //         $pet_owner_query->latest()->get(),
    //     );
    // }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $filter = $request->query('filter'); // addr_brgy
        $count = $request->query('count'); // "All" or specific barangay
        $type = $request->query('type', 'monthly'); // "monthly" or "yearly"

        $pet_owner_query = PetOwner::query();

        if (!empty($filter)) {
            $pet_owner_query->where('addr_brgy', 'like', '%' . $filter . '%');
        }

        if (!empty($search)) {
            $pet_owner_query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('addr_brgy', 'like', '%' . $search . '%');
            });
        }
        // Calculate total count of matching records
        $totalCountQuery = clone $pet_owner_query;
        if (!empty($count) && $count !== 'All') {
            $totalCountQuery->where('addr_brgy', $count);
        }
        $totalCount = $totalCountQuery->count();
        // Determine the first created_at date
        $firstRecord = PetOwner::orderBy('created_at', 'asc')->first();
        if (!$firstRecord) {
            return response()->json([
                'categories' => [],
                'data' => [],
                'total_count' => $totalCount,
            ]);
        }

        $firstDate = Carbon::parse($firstRecord->created_at)->subYear();
        $currentDate = Carbon::now();

        if(!empty($type) && !empty($count)){
            if ($type === 'yearly') {
                // Yearly stats
                $yearlyStats = collect([]);
                for ($year = $firstDate->year; $year <= $currentDate->year; $year++) {
                    $startOfYear = Carbon::createFromDate($year, 1, 1)->startOfYear();
                    $endOfYear = Carbon::createFromDate($year, 1, 1)->endOfYear();

                    // $yearlyCount = $pet_owner_query->whereBetween('created_at', [$startOfYear, $endOfYear]);
                    $yearlyCount = PetOwner::query()->whereBetween('created_at', [$startOfYear, $endOfYear]);

                    if (!empty($count) && $count !== 'All') {
                        $yearlyCount->where('addr_brgy', $count);
                    }

                    $yearlyStats->push([
                        'year' => $year,
                        'count' => $yearlyCount->count(),
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
                // Monthly stats for the current year
                $monthlyStats = collect([]);

                for ($i = 0; $i < 12; $i++) {
                    $startOfMonth = $currentDate->copy()->startOfYear()->addMonths($i);
                    $endOfMonth = $startOfMonth->copy()->endOfMonth();

                    if ($startOfMonth->gt($currentDate)) break;

                    // Clone the original query builder or use a fresh query for each iteration
                    $monthlyCount = PetOwner::query()
                        ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);


                    if (!empty($count) && $count !== 'All') {
                        $monthlyCount->where('addr_brgy', $count);
                    }

                    $monthlyStats->push([
                        'month' => $startOfMonth->format('M'),
                        'count' => $monthlyCount->count(),
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
        return PetOwnerResource::collection(
            $pet_owner_query->latest()->get(),
        );
    }

    public function getPetOwnersWithPetsAndMedications()
    {
        // Retrieve all pet owners with their pets and medications
        $petOwners = PetOwner::with([
            'pets.medications.veterinarian', // Eager load pets and their medications
            'pets.medications.medicationname' // Load medication name details
        ])->get();

        // Map the data into the required structure
        $formattedData = $petOwners->map(function ($owner) {
            return [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'phone_number' => $owner->phone_number,
                'addr_zone' => $owner->addr_zone,
                'addr_brgy' => $owner->addr_brgy,
                'pets' => $owner->pets->map(function ($pet) {
                    return [
                        'id' => $pet->id,
                        'name' => $pet->name,
                        'breed' => $pet->breed,
                        'gender' => $pet->gender,
                        'color_description' => $pet->color_description,
                        'size' => $pet->size,
                        'weight' => $pet->weight,
                        'date_of_birth' => $pet->date_of_birth,
                        'status' => $pet->status,
                        'medications' => $pet->medications->map(function ($medication) {
                            return [
                                'medication_date' => $medication->medication_date,
                                'type' => $medication->medicationname->medtype ?? 'N/A',
                                'name' => $medication->medicationname->name ?? 'N/A',
                                'batch_number' => $medication->batch_number,
                                'or_number' => $medication->or_number,
                                'veterinarian' => $medication->veterinarian->name ?? 'N/A',
                                'expiry' => $medication->expiry_date,
                                'next_vaccination' => $medication->next_vaccination,
                                'fee' => $medication->fee,
                                'remarks' => $medication->remarks,
                            ];
                        }),
                    ];
                }),
            ];
        });

        // Return the formatted data as JSON
        return response()->json($formattedData);
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