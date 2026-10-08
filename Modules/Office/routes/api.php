<?php

use Illuminate\Support\Facades\Route;
use Modules\Office\Http\Controllers\OfficeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('offices', OfficeController::class)->names('office');
});
