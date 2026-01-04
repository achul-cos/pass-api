<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\JadwalController as V1JadwalController;
use App\Http\Controllers\Api\V1\TiketController as V1TiketController;
use App\Http\Controllers\Api\V1\KendaraanController as V1KendaraanController;
use App\Http\Controllers\Api\V1\PenumpangController as V1PenumpangController;
use App\Http\Controllers\Api\V1\PelabuhanController as V1PelabuhanController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';

Route::prefix('v1')->group(function () {
    Route::apiResource('jadwals', V1JadwalController::class);
    Route::post('jadwals/restore/{id}', [V1JadwalController::class, 'restore'])->name('Jadwals.restore');

    Route::apiResource('tikets', V1TiketController::class);
    Route::post('tikets/restore/{id}', [V1TiketController::class, 'restore'])->name('Tikets.restore');
    Route::post('tikets/validate', [V1TiketController::class, 'validate'])->name('Tikets.validate');
    Route::post('tikets/validate/validateWithOutNomorKendaraan', [V1TiketController::class, 'validateWithOutNomorKendaraan'])->name('Tikets.validateWithOutNomorKendaraan');

    Route::apiResource('kendaraans', V1KendaraanController::class);
    Route::post('kendaraans/restore/{id}', [V1KendaraanController::class, 'restore'])->name('Kendaraans.restore');

    route::apiResource('penumpangs', V1PenumpangController::class);
    Route::post('penumpangs/validate', [V1PenumpangController::class, 'validate'])->name('Penumpangs.validate');

    route::apiResource('pelabuhan', V1PelabuhanController::class);
});


// Route::prefix('v1')->group(function () {
//     Route::middleware('auth:sanctum_penumpang')->group(function () {
//         Route::apiResource('jadwals', V1JadwalController::class);
//         Route::post('jadwals/restore/{id}', [V1JadwalController::class, 'restore'])->name('Jadwals.restore');

//         Route::apiResource('tikets', V1TiketController::class);
//         Route::post('tikets/restore/{id}', [V1TiketController::class, 'restore'])->name('Tikets.restore');
//         Route::post('tikets/validate', [V1TiketController::class, 'validate'])->name('Tikets.validate');
//         Route::post('tikets/validate/validateWithOutNomorKendaraan', [V1TiketController::class, 'validateWithOutNomorKendaraan'])->name('Tikets.validateWithOutNomorKendaraan');

//         Route::apiResource('kendaraans', V1KendaraanController::class);
//         Route::post('kendaraans/restore/{id}', [V1KendaraanController::class, 'restore'])->name('Kendaraans.restore');        
//     });    
// });
