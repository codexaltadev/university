<?php

use Illuminate\Support\Facades\Route;
use Modules\ThemeManager\Http\ScreenshotController;

// Route names start with "theme-manager."
// Theme folders are not public: screenshots for the Themes screen (View:Theme)
Route::get('/admin/theme-screenshots/{slug}', ScreenshotController::class)
    ->where('slug', '[a-z][a-z0-9-]*')->name('theme-manager.screenshot');
