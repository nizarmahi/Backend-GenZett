<?php

use App\Http\Controllers\API\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\SportController;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\FieldController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\OTPController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\UserController;

Route::prefix('reservation')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/', [ReservationController::class, 'index']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}', [ReservationController::class, 'update']);
    Route::delete('/{id}', [ReservationController::class, 'destroy']);
    Route::get('/{id}/details', [ReservationController::class, 'details']);
    Route::get('/{id}/payment', [ReservationController::class, 'payment']);
    Route::put('/{id}/status', [ReservationController::class, 'updatePaymentStatus']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/


// Auth Routes
Route::prefix('users')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); 
    Route::get('/', [AuthController::class, 'index']);             
    Route::get('/{id}', [AuthController::class, 'show']);          
    Route::put('/{id}', [AuthController::class, 'update']);        
    Route::delete('/{id}', [AuthController::class, 'destroy']);    
});
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register/send-otp', [OTPController::class, 'sendOtp']);
// Route::post('/register/verify', [OTPController::class, 'verifyOtpAndRegister']);

// Public routes
// Sport API routes
Route::group(['prefix' => 'sports'], function () {
    Route::get('/', [SportController::class, 'index']);
    Route::get('/{id}', [SportController::class, 'show']);
    Route::post('/', [SportController::class, 'store']);
    Route::put('/{id}', [SportController::class, 'update']);
    Route::delete('/{id}', [SportController::class, 'destroy']);
});
// Location API routes
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::post('/', [LocationController::class, 'store']);
    Route::get('/sports', [LocationController::class, 'getAllSports']);
    Route::get('/{id}', [LocationController::class, 'show']);
    Route::get('/{id}/sports', [LocationController::class, 'getLocationSports']);
    Route::put('/{id}', [LocationController::class, 'update']);
    Route::delete('/{id}', [LocationController::class, 'destroy']);
});
// Field API routes
Route::group(['prefix' => 'fields'], function () {
    Route::get('/', [FieldController::class, 'index']);
    Route::post('/', [FieldController::class, 'store']);
    Route::get('/sports', [FieldController::class, 'getAllSports']);
    Route::get('/locations', [FieldController::class, 'getAllLocations']);
    Route::get('/{id}', [FieldController::class, 'show']);
    Route::put('/{id}', [FieldController::class, 'update']);
    Route::delete('/{id}', [FieldController::class, 'destroy']);
});
// Admin API routes
Route::group(['prefix' => 'admins'], function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::post('/', [AdminController::class, 'store']);
    Route::get('/{id}', [AdminController::class, 'show']);
    Route::put('/{id}', [AdminController::class, 'update']);
    Route::delete('/{id}', [AdminController::class, 'destroy']);
});
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});
Route::group(['prefix' => 'schedules'], function () {
    Route::get('/', [ScheduleController::class, 'index']);
});
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
        // Route::group(['prefix' => 'sports'], function () {
        //     Route::get('/', [SportController::class, 'index']);
        //     Route::get('/{id}', [SportController::class, 'show']);
        //     Route::post('/', [SportController::class, 'store']);
        //     Route::put('/{id}', [SportController::class, 'update']);
        //     Route::delete('/{id}', [SportController::class, 'destroy']);
        // });

        // Locations management
        // Location API routes
        // Route::group(['prefix' => 'locations'], function () {
        //     Route::get('/', [LocationController::class, 'index']);
        //     Route::post('/', [LocationController::class, 'store']);
        //     Route::get('/sports', [LocationController::class, 'getAllSports']);
        //     Route::get('/{id}', [LocationController::class, 'show']);
        //     Route::get('/{id}/sports', [LocationController::class, 'getLocationSports']);
        //     Route::put('/{id}', [LocationController::class, 'update']);
        //     Route::delete('/{id}', [LocationController::class, 'destroy']);
        // });

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
