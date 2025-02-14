<?php

use App\Http\Controllers\ArgusController;
use App\Http\Controllers\BoltrackUpdateController;
use App\Http\Controllers\CallsController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UploadsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth', 'verified')->group(function () {

    Route::get('/uploads/index', function () {return view('uploads.index');})->name('uploads.index');


    Route::get('/uploads/index/matriz', [UploadsController::class, 'getMatriz'])->name('uploads.index.matriz');
    Route::post('/uploads/index/matriz', [UploadsController::class, 'postMatriz']);
    Route::get('/uploads/index/matriz/index', [UploadsController::class, 'listMatrix'])->name('uploads.index.matriz.index');
    Route::delete('/uploads/index/{batch_id}/matrix', [UploadsController::class, 'destroy'])->name('uploads.index.matrix.destroy');


    Route::get('/uploads/index/truck', [UploadsController::class, 'getTruck'])->name('uploads.index.truck');
    Route::post('/uploads/index/truck', [UploadsController::class, 'postTruck'])->name('uploads.index.truck.post');
    Route::get('/uploads/index/truck/index', [UploadsController::class, 'listTruck'])->name('uploads.index.truck.index');

    Route::get('/uploads/index/argus', [UploadsController::class, 'getArgus'])->name('uploads.index.argus');
    Route::post('/uploads/index/argus', [UploadsController::class, 'postArgus'])->name('uploads.index.argus.post');
    Route::get('/uploads/index/argus/index', [UploadsController::class, 'listArgus'])->name('uploads.index.argus.index');


    Route::get('/uploads/index/matrix/{batch_id}/work', [UploadsController::class, 'workWith'])->name('uploads.index.matrix.work');


    Route::get('/uploads/index/truck/work', [UploadsController::class, 'workWith'])->name('uploads.index.truck.work');
    Route::get('/uploads/index/truck/destroy', [UploadsController::class, 'workWith'])->name('uploads.index.truck.destroy');


    Route::get('/work/matrix/{patente}/call', [CallsController::class, 'registerCall'])->name('work.matrix.call');
    Route::post('/work/matrix/call/save', [CallsController::class, 'saveCall'])->name('work.matrix.call.save');


    Route::get('argus/files/select', [ArgusController::class, 'selectFiles'])->name('argus.files.select');
    Route::post('argus/files/process', [ArgusController::class, 'processFiles'])->name('argus.files.process');
    Route::post('argus/files/process/download', [ArgusController::class, 'downloadExcel'])->name('argus.files.process.download');


    Route::get('/boltrack/update', [BoltrackUpdateController::class, 'update'])->name('boltrack.update');


    Route::get('drivers/index', [DriverController::class, 'index'])->name('drivers.index');



    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
