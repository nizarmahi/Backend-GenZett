<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClosedController;
use App\Http\Controllers\API\FieldController;
use App\Http\Controllers\API\JWTAuth;
use App\Http\Controllers\API\LocationController;
use App\Http\Controllers\API\MembershipController;
use App\Http\Controllers\API\OTPController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\SportController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\HistoryController;
use App\Http\Controllers\API\SuperAdmin\DashboardController;
use App\Http\Controllers\API\Admin\AdminDashboardController;
use App\Http\Controllers\API\CancellationController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Auth::routes(['verify' => true]);
// Register routes
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::middleware('auth:api')->group(function () {
Route::put('editAdminProfile/{id}', [AuthController::class, 'editAdminProfile']);
Route::post('change-password', [AuthController::class, 'changePassword']);
});


// Dashboard routes
// routes/api.php
Route::prefix('superadmin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

Route::prefix('admin')->group(function () {
    Route::get('/dashboard/{locationId}', [AdminDashboardController::class, 'getDashboardAdmin']);
});

// Public routes
// Sport API routes
Route::group(['prefix' => 'sports'], function () {
    Route::get('/', [SportController::class, 'index']);
    Route::get('/fieldsCount', [SportController::class, 'fieldsCount']);
    Route::get('/{id}', [SportController::class, 'show']);
    Route::post('/', [SportController::class, 'store']);
    Route::put('/{id}', [SportController::class, 'update']);
    Route::delete('/{id}', [SportController::class, 'delete']);
    Route::get('/allSports', [SportController::class, 'getAllSports']);
});

// Location API routes
Route::group(['prefix' => 'locations'], function () {
    Route::get('/', [LocationController::class, 'index']);
    Route::post('/', [LocationController::class, 'store']);
    Route::get('/allSports', [LocationController::class, 'getAllSports']);///
    Route::get('/allLocations', [LocationController::class, 'getAllLocations']);
    Route::get('/{id}', [LocationController::class, 'show']);

    Route::get('/{id}/sports', [LocationController::class, 'getLocationSports']);///
    Route::put('/{id}', [LocationController::class, 'update']);
    Route::delete('/{id}', [LocationController::class, 'delete']);
});
Route::prefix('closed')->group(function () {
    Route::get('/', [ClosedController::class, 'index']);
    Route::post('/', [ClosedController::class, 'store']);
    Route::get('/{id}', [ClosedController::class, 'show']);
    Route::put('/{id}', [ClosedController::class, 'update']);
    Route::delete('/{id}', [ClosedController::class, 'destroy']);
});

// Field API routes
Route::group(['prefix' => 'fields'], function () {
    Route::get('/', [FieldController::class, 'index']);
    Route::post('/', [FieldController::class, 'store']);
    Route::get('/allFields', [FieldController::class, 'getAllFields']);
    Route::get('/{id}', [FieldController::class, 'show']);
    Route::put('/{id}', [FieldController::class, 'update']);
    Route::delete('/{id}', [FieldController::class, 'delete']);
    Route::get('/availableTimes/{fieldId}', [FieldController::class, 'getAvailableTimes']);
    Route::get('/getPrice/{id}', [FieldController::class, 'getPrice']);
});

Route::group(['prefix' => 'reservations'], function () {
    Route::get('/location', [ReservationController::class, 'getAllLocations']);
    Route::get('/sport', [ReservationController::class, 'getSports']);
    Route::get('/sport/{locationId}', [ReservationController::class, 'getSportsByLocation']);
    Route::get('/{locationId}/schedules', [ReservationController::class, 'getScheduleByLocation']);
    Route::get('/getPrice/{locationId}', [ReservationController::class, 'getPriceByLocation']);
    Route::get('/{locationId}/minimumPrice', [ReservationController::class, 'getMinPriceByLocation']);
    Route::get('/getMinPriceLocSport', [ReservationController::class, 'getMinPriceByLocationSport']);

    Route::get('/', [ReservationController::class, 'index']);
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}', [ReservationController::class, 'update']);
    Route::put('/{id}/status', [ReservationController::class, 'updatePaymentStatus']);
    Route::post('/{id}/pay', [ReservationController::class, 'confirmPayment']);

    // Route::get('/user/{userId}/detail', [ReservationController::class, '']);
});
Route::group(['prefix' => 'history'], function () {
    Route::get('/user/{id}', [HistoryController::class, 'userReservations']);
});

Route::group(['prefix' => 'cancellations'], function () {
    Route::get('/', [CancellationController::class, 'index']);
    Route::post('/{id}/refund', [CancellationController::class, 'refund']);
    Route::post('/{id}/dp', [CancellationController::class, 'cancellationDP']);
    Route::post('/refund', [CancellationController::class, 'refundApplication']);
});
// Payment API routes
Route::group(['prefix' => 'payments'], function () {
    Route::post('/webhook', [PaymentController::class, 'webhook']);
    Route::get('/failed', [PaymentController::class, 'handleFailedPayment']);
    Route::put('/{id}/status', [PaymentController::class, 'updatePaymentStatus']);
    Route::get('/{id}/status', [PaymentController::class, 'getPaymentStatus']);

    Route::get('/', [PaymentController::class, 'index']);
    Route::post('/', [PaymentController::class, 'store']);
    Route::get('/{id}', [PaymentController::class, 'show']);
    Route::put('/{id}', [PaymentController::class, 'update']);
    Route::delete('/{id}', [PaymentController::class, 'destroy']);
    Route::post('/webhook', [PaymentController::class, 'handleWebhook']);
});
// Membership API routes
Route::group(['prefix' => 'memberships'], function () {
    Route::get('/', [MembershipController::class, 'index']);
    Route::post('/', [MembershipController::class, 'store']);
    Route::get('/{id}', [MembershipController::class, 'show']);
    Route::put('/{id}', [MembershipController::class, 'update']);
    Route::delete('/{id}', [MembershipController::class, 'destroy']);
});

// Admin API routes
Route::group(['prefix' => 'admins'], function () {
    Route::get('/', [AdminController::class, 'index']);
    Route::post('/', [AdminController::class, 'store']);
    Route::get('/{id}', [AdminController::class, 'show']);
    Route::put('/{id}', [AdminController::class, 'update']);
    Route::delete('/{id}', [AdminController::class, 'destroy']);
});

// User API routes
Route::group(['prefix' => 'users'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::put('/{id}/change-password', [UserController::class, 'changePassword']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

Route::group(['prefix' => 'schedules'], function () {
    Route::get('/', [ScheduleController::class, 'index']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Admin routes
    Route::middleware('super-admin')->group(function () {
    });

    Route::middleware('admin')->group(function () {
    });

    Route::get('/histories', [HistoryController::class, 'index']);
});
