<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\JobStatusApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta para verificar el estado de los jobs
Route::get('/job-status', [JobStatusApiController::class, 'getStatus']);
