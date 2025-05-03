<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SportController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\FieldController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Route prefix untuk versioning API
Route::prefix('v1')->group(function () {
    // Auth Routes
    // Route::post('/register', [AuthController::class, 'register']);
    // Route::post('/login', [AuthController::class, 'login']);
    
    // Public routes
    Route::get('/sports', [SportController::class, 'index']);
    Route::get('/sports/{id}', [SportController::class, 'show']);
    Route::post('/sports', [SportController::class, 'store']);
    Route::put('/sports/{id}', [SportController::class, 'update']);
    Route::delete('/sports/{id}', [SportController::class, 'destroy']);
    // Route::get('/locations', [LocationController::class, 'index']);
    // Route::get('/locations/{id}', [LocationController::class, 'show']);
    // Route::get('/fields', [FieldController::class, 'index']);
    // Route::get('/fields/{id}', [FieldController::class, 'show']);
    // Route::get('/admins', [AdminController::class, 'index']); 
    // Route::get('/admins/{id}', [AdminController::class, 'show']);
    // Route::get('/users', [UserController::class, 'index']); 
    // Route::get('/users/{id}', [UserController::class, 'show']);
    // Route::get('/reservations', [ReservationController::class, 'index']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // User profile
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        // Route::post('/logout', [AuthController::class, 'logout']);
        
        // Reservations
        // Route::apiResource('reservations', ReservationController::class);
        
        // Admin routes
        Route::middleware('super-admin')->group(function () {
            // Sports management
            // Route::post('/sports', [SportController::class, 'store']);
            // Route::put('/sports/{id}', [SportController::class, 'update']);
            // Route::delete('/sports/{id}', [SportController::class, 'destroy']);
            
            // Locations management
            // Route::post('/locations', [LocationController::class, 'store']);
            // Route::put('/locations/{id}', [LocationController::class, 'update']);
            // Route::delete('/locations/{id}', [LocationController::class, 'destroy']);
            
            // Fields management
            // Route::post('/fields', [FieldController::class, 'store']);
            // Route::put('/fields/{id}', [FieldController::class, 'update']);
            // Route::delete('/fields/{id}', [FieldController::class, 'destroy']);
            
            // Admins management
            // Route::post('/admins', [AdminController::class, 'store']);
            // Route::put('/admins/{id}', [AdminController::class, 'update']);
            // Route::delete('/admins/{id}', [AdminController::class, 'destroy']);
            
            // Users management
            // Route::put('/users/{id}', [UserController::class, 'update']);
            // Route::delete('/users/{id}', [UserController::class, 'destroy']);
        });

        Route::middleware('admin')->group(function () {
            // Reservation management
            // Route::post('/reservation', [ReservationController::class, 'store']);
            // Route::put('/reservation/{id}', [ReservationController::class, 'update']);
            
            // Fields management
            // Route::post('/fields', [FieldController::class, 'store']);
            // Route::put('/fields/{id}', [FieldController::class, 'update']);
            // Route::delete('/fields/{id}', [FieldController::class, 'destroy']);
        });
    });
});