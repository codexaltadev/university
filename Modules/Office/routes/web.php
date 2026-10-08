<?php

use Illuminate\Support\Facades\Route;
use Modules\Office\Http\Controllers\OfficeController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('offices', OfficeController::class)->names('office');
});
