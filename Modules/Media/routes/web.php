<?php

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\MediaFileController;

// Route names start with "media."
// Stable file address (FR-MEDIA-07); private files need a valid signature (BRL-MEDIA-02)
Route::get('/media/{item}/{name?}', MediaFileController::class)
    ->whereNumber('item')
    ->name('media.file');
