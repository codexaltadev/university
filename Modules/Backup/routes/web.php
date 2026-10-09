<?php

use Illuminate\Support\Facades\Route;
use Modules\Backup\Http\GoogleDriveController;

// Route names start with "backup." — downloads go through the admin page (BRL-BKP-01).
// "Connect Google account" for the Google Drive destination (Super Admins).
Route::prefix('admin/backup/google-drive')->name('backup.gdrive.')->group(function (): void {
    Route::get('connect', [GoogleDriveController::class, 'connect'])->name('connect');
    Route::get('callback', [GoogleDriveController::class, 'callback'])->name('callback');
});
