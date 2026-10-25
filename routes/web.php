<?php

use App\Http\Controllers\FmsFileController;
use Illuminate\Support\Facades\Route;

// The website homepage and theme preview routes come from the kernel
// (PlatformServiceProvider::registerRoutes); module pages from their modules.

// A teacher's CV / vCard from the FMS API, through this site (the token stays on the server)
Route::get('/fms/{faculty}/{department}/{teacher}/{file}', FmsFileController::class)
    ->whereIn('file', ['cv', 'vcard'])
    ->where(['faculty' => '[A-Za-z0-9_-]+', 'department' => '[A-Za-z0-9_-]+', 'teacher' => '[A-Za-z0-9_.-]+'])
    ->middleware('throttle:30,1')
    ->name('fms.file');
