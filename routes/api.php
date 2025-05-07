<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\PaymentController;

Route::prefix('reservation')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::get('/', [ReservationController::class, 'index']);
    Route::get('/{id}', [ReservationController::class, 'show']);
    Route::put('/{id}', [ReservationController::class, 'update']);
    Route::delete('/{id}', [ReservationController::class, 'destroy']);
    Route::get('/{id}/details', [ReservationController::class, 'details']);
    Route::get('/{id}/payment', [ReservationController::class, 'payment']);
    Route::put('/{id}/status', [ReservationController::class, 'updatePaymentStatus']);

    Route::post('/{id}/payment', [PaymentController::class, 'store']);
});


