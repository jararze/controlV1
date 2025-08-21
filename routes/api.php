<?php

use App\Http\Controllers\TruckTrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobStatusApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta para verificar el estado de los jobs
Route::get('/job-status', [JobStatusApiController::class, 'getStatus']);
Route::prefix('api/truck-tracking')->name('api.truck-tracking.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/trucks', [TruckTrackingController::class, 'index'])->name('trucks.index');
    Route::get('/trucks/{truckTracking}', [TruckTrackingController::class, 'show'])->name('trucks.show');
    Route::get('/alerts', [TruckTrackingController::class, 'alerts'])->name('alerts');
    Route::post('/process', [TruckTrackingController::class, 'processTracking'])->name('process');
});
