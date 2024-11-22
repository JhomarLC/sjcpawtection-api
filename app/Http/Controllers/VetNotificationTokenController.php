<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationTokensResource;
use App\Http\Resources\VetNotificationTokensResource;
use App\Models\NotificationTokens;
use App\Models\PetOwner;
use App\Models\Veterinarians;
use App\Models\VetNotificationTokens;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class VetNotificationTokenController extends Controller
{
 // Get all notification tokens
 public function index()
 {
     $tokens = VetNotificationTokens::with('veterinarian')->latest()->get();

     return VetNotificationTokensResource::collection($tokens);
 }

 // Create a new notification token
 public function store(Request $request)
 {
     try {
         // Validate the request data
         $validatedData = $request->validate([
             'veterinarians_id' => 'required|exists:veterinarians,id',
             'token' => 'required|string|unique:vet_notification_tokens,token',
         ]);

         // Create the notification token
         $token = VetNotificationTokens::create($validatedData);

         // Return the newly created token resource with a 201 status code
         return (new VetNotificationTokensResource($token))
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
         $token = VetNotificationTokens::with('veterinarian')->findOrFail($id);

         // Return the token as a resource
         return new VetNotificationTokensResource($token);

     } catch (ModelNotFoundException $e) {
         // Return a JSON response if the token is not found
         return response()->json([
             'success' => false,
             'message' => 'Notification token not found',
         ], 404);
     }
 }

 public function destroy(VetNotificationTokens $token)
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
         'status' => 'string|nullable',
     ]);

     // Query the PetOwner model based on the provided filters
     $query = Veterinarians::query();

     if ($request->filled('status')) {
         $query->where('status', $validated['status']);
     }

     // Fetch pet owners and their notification tokens
     $veterinarians = $query->with('notificationsTokens')->get();

     // Extract tokens from pet owners
     $tokens = $veterinarians->flatMap(function ($veterinarian) {
         return $veterinarian->notificationsTokens;
     });

     // Return tokens in a JSON response
     return VetNotificationTokensResource::collection($tokens->values());
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
}
