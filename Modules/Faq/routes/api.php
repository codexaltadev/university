<?php

use Illuminate\Support\Facades\Route;
use Modules\Faq\Http\Controllers\FaqController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('faqs', FaqController::class)->names('faq');
});
