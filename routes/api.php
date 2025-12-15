<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\JadwalController as V1JadwalController;
use App\Http\Controllers\Api\V1\TiketController as V1TiketController;
use App\Http\Controllers\Api\V1\KendaraanController as V1KendaraanController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('jadwals', V1JadwalController::class);
    Route::post('jadwals/restore/{id}', [V1JadwalController::class, 'restore'])->name('Jadwals.restore');

    Route::apiResource('tikets', V1TiketController::class);
    Route::post('tikets/restore/{id}', [V1TiketController::class, 'restore'])->name('Tikets.restore');
    Route::post('tikets/validate', [V1TiketController::class, 'validate'])->name('Tikets.validate');
    Route::post('tikets/validate/validateWithOutNomorKendaraan', [V1TiketController::class, 'validateWithOutNomorKendaraan'])->name('Tikets.validateWithOutNomorKendaraan');

    Route::apiResource('kendaraans', V1KendaraanController::class);
    Route::post('kendaraans/restore/{id}', [V1KendaraanController::class, 'restore'])->name('Kendaraans.restore');
});
