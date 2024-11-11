<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PetOwnerAuthController;
use App\Http\Controllers\Api\VeterinarianAuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationNameController;
use App\Http\Controllers\MedicationTypeController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\PetOwnerController;
use App\Http\Controllers\VeterinarianController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
// ADMIN
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ADMIN
Route::middleware('auth:sanctum')->group(function() {
    Route::post('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// VETERINARIANS
Route::post('veterinarian/register', [VeterinarianAuthController::class, 'register']);
Route::post('veterinarian/login', [VeterinarianAuthController::class, 'login']);

// PET OWNERS
Route::post('petowner/register', [PetOwnerAuthController::class, 'register']);
Route::post('petowner/login', [PetOwnerAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function() {
    // VETERINARIANS
    Route::post('veterinarian/profile', [VeterinarianAuthController::class, 'profile']);
    Route::post('veterinarian/logout', [VeterinarianAuthController::class, 'logout']);

    Route::get('veterinarians', [VeterinarianController::class, 'index']);
    Route::get('veterinarians/{vet}', [VeterinarianController::class, 'show']);

    Route::put('veterinarians/{vet}/approve', [VeterinarianController::class, 'approve']);
    Route::put('veterinarians/{vet}/decline', [VeterinarianController::class, 'decline']);
    Route::put('veterinarians/{vet}/archive', [VeterinarianController::class, 'archive']);
    Route::post('veterinarians/{vet}', [VeterinarianController::class, 'update']);

    // PET OWNERS
    Route::post('petowner/profile', [PetOwnerAuthController::class, 'profile']);
    Route::post('petowner/logout', [PetOwnerAuthController::class, 'logout']);

    Route::get('petowners', [PetOwnerController::class, 'index']);
    Route::get('petowners/{petowner}', [PetOwnerController::class, 'show']);
    Route::post('petowners/{petowner}', [PetOwnerController::class, 'update']);

    // PET
    Route::get('petowners/{petowner}/pets', [PetController::class, 'index']);
    Route::post('petowners/{petowner}/pets', [PetController::class, 'store']);
    Route::get('petowners/{petowner}/pets/{pet}', [PetController::class, 'show']);

    // Route::post('petowners/{petowner}/pets/{pet}/deceased', [PetController::class, 'deceased']);
    Route::post('pets/{pet}/approve', [PetController::class, 'approve']);
    Route::post('pets/{pet}/decline', [PetController::class, 'decline']);

    Route::post('petowners/{petowner}/pets/{pet}/addphotos', [PetController::class, 'addphotos']);
    Route::get('petowners/{petowner}/pets/{pet}/getphotos', [PetController::class, 'getphotos']);

    // MEDICATIONS
    Route::get('pets/{pet}/medications', [MedicationController::class, 'index']);
    Route::post('pets/{pet}/medications', [MedicationController::class, 'store']);
    Route::get('pets/{pet}/medications/{medication}', [MedicationController::class, 'show']);

    // MEDICATION NAMES
    Route::get('medtype/{medtype}/mednames', [MedicationController::class, 'index']);
    Route::post('medtype/{medtype}/mednames', [MedicationController::class, 'store']);
    Route::get('medtype/{medtype}/mednames/{medname}', [MedicationController::class, 'show']);
    Route::put('medtype/{medtype}/mednames/{medname}', [MedicationController::class, 'update']);
    Route::delete('medtype/{medtype}/mednames/{medname}', [MedicationController::class, 'destroy']);

    // MEDICATION TYPES
    Route::get('medtype', [MedicationTypeController::class, 'index']);
    Route::post('medtype', [MedicationTypeController::class, 'store']);
    Route::get('medtype/{medtype}', [MedicationTypeController::class, 'show']);
    Route::put('medtype/{medtype}', [MedicationTypeController::class, 'update']);
    Route::delete('medtype/{medtype}', [MedicationTypeController::class, 'destroy']);

    // MEDICATION NAMES
    Route::get('medtype/{medtype}/medname', [MedicationNameController::class, 'index']);
    Route::post('medtype/{medtype}/medname', [MedicationNameController::class, 'store']);
    Route::get('medtype/{medtype}/medname/{medname}', [MedicationNameController::class, 'show']);
    Route::put('medtype/{medtype}/medname/{medname}', [MedicationNameController::class, 'update']);
    Route::delete('medtype/{medname}/medname-delete', [MedicationNameController::class, 'destroy']);

    Route::post('medtype/{medtype}/medname/{medname}/archive', [MedicationNameController::class, 'archive']);
    Route::post('medtype/{medtype}/medname/{medname}/unarchive', [MedicationNameController::class, 'unarchive']);

    // EVENTS
    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::put('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);
});
