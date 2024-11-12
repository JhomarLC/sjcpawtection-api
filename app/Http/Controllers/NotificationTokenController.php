<?php

namespace App\Http\Controllers;

use App\Http\Resources\MedicationResource;
use App\Http\Resources\NotificationTokensResource;
use App\Models\NotificationTokens;
use App\Models\PetOwner;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class NotificationTokenController extends Controller
{
    // Get all notification tokens
    public function index()
    {
        $tokens = NotificationTokens::with('petowner')->latest()->get();

        return NotificationTokensResource::collection($tokens);
    }

    // Create a new notification token
    public function store(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'pet_owner_id' => 'required|exists:pet_owners,id',
                'token' => 'required|string|unique:notification_tokens,token',
            ]);

            // Create the notification token
            $token = NotificationTokens::create($validatedData);

            // Return the newly created token resource with a 201 status code
            return (new NotificationTokensResource($token))
                    ->response()
                    ->setStatusCode(201);
        } catch (ValidationException $e) {
            // Return a response with the validation errors and a 422 Unprocessable Entity status code
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    // Show a specific notification token by ID
    public function show($id)
    {
        try {
            // Find the notification token or throw a ModelNotFoundException
            $token = NotificationTokens::with('petowner')->findOrFail($id);

            // Return the token as a resource
            return new NotificationTokensResource($token);

        } catch (ModelNotFoundException $e) {
            // Return a JSON response if the token is not found
            return response()->json([
                'success' => false,
                'message' => 'Notification token not found',
            ], 404);
        }
    }

    public function destroy(NotificationTokens $token)
    {
        // Delete the token
        $token->delete();

        // Return a success message
        return response()->json(['message' => 'Notification token deleted successfully'], 200);
    }

    public function getByAddress(Request $request)
    {
        // Validate query parameters
        $validated = $request->validate([
            'addr_zone' => 'string|nullable',
            'addr_brgy' => 'string|nullable',
        ]);

        // Query the PetOwner model based on the provided filters
        $query = PetOwner::query();

        if ($request->filled('addr_zone')) {
            $query->where('addr_zone', $validated['addr_zone']);
        }

        if ($request->filled('addr_brgy')) {
            $query->where('addr_brgy', $validated['addr_brgy']);
        }

        // Fetch pet owners and their notification tokens
        $petOwners = $query->with('notificationsTokens')->get();

        // Extract tokens from pet owners
        $tokens = $petOwners->flatMap(function ($petOwner) {
            return $petOwner->notificationsTokens;
        });

        // Return tokens in a JSON response
        return NotificationTokensResource::collection($tokens->values());
    }

    public function sendNotification(Request $request)
    {
        // Validate request data (optional but recommended)
        $request->validate([
            'to' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        // Define the notification payload
        $notificationData = [
            'to' => $request->to,
            'title' => $request->title,
            'body' => $request->body,
        ];

        // Forward the request to Expo's API
        try {
            $response = Http::withOptions([
                'verify' => false, // Disables SSL verification
            ])->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate',
            ])->post('https://exp.host/--/api/v2/push/send', $notificationData);

            // Return the response from Expo's API back to the client
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to send notification' . $e], 500);
        }
    }


    // public function getByAddress(Request $request)
    // {

    //     $search = $request->query('get-by-address');
    //     dd($search);
    //     // Validate the query parameters
    //     // $validated = $request->validate([
    //     //     'addr_zone' => 'string|nullable',
    //     //     'addr_brgy' => 'string|nullable',
    //     // ]);

    //     // // Start a query on the PetOwner model
    //     // $query = PetOwner::query();

    //     // // Apply filters based on the presence of query parameters
    //     // if ($request->filled('addr_zone')) {
    //     //     $query->where('addr_zone', $validated['addr_zone']);
    //     // }

    //     // if ($request->filled('addr_brgy')) {
    //     //     $query->where('addr_brgy', $validated['addr_brgy']);
    //     // }

    //     // // Fetch pet owners with matching addresses and include their notification tokens
    //     // $petOwners = $query->with('notificationTokens')->get();

    //     // // Flatten the collection of tokens from the pet owners
    //     // $tokens = $petOwners->flatMap(function ($petOwner) {
    //     //     return $petOwner->notificationTokens;  // Ensure the relationship name is correct
    //     // });

    //     // // Return the tokens as a resource collection
    //     // return NotificationTokensResource::collection($tokens->values());
    // }
}