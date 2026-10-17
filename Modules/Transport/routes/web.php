<?php

use Illuminate\Support\Facades\Route;
use Modules\Transport\Support\Timetable;

// Route names start with "transport."

// The old transport system's public API, the same JSON (DB-06 v1.2 §2): DIU's current frontend only
// changes the address. Read-only, open to any site (the frontend calls it from the browser).
Route::get('/api/v2/public/transport', fn (Timetable $timetable) => response()->json($timetable->api(), 200, [
    'Access-Control-Allow-Origin' => '*',
    'Cache-Control' => 'public, max-age=300',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))->name('transport.api');
