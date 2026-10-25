<?php

use App\Services\FmsApiClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('fms:clear-cache', function (FmsApiClient $fms) {
    $this->info('Forgot '.$fms->clearCache().' cached FMS API answers.');
})->purpose('Forget the cached FMS API answers (fms:* keys)');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
