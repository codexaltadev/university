<?php

namespace Modules\Admission\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\Circulars\CircularResource;
use Modules\Admission\Filament\Resources\ContactGroups\AdmissionContactGroupResource;
use Modules\Admission\Filament\Resources\Highlights\AdmissionHighlightResource;
use Modules\Admission\Filament\Resources\ResultBatches\ResultBatchResource;
use Modules\Admission\Filament\Resources\Steps\AdmissionStepResource;
use Modules\Admission\Filament\Resources\Subscribers\AdmissionSubscriberResource;

/** Academic › Admission: circulars, information, steps, contacts, test results, subscribers. */
class AdmissionPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'admission';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([AdmissionCluster::class]);
        $panel->resources([
            CircularResource::class, AdmissionHighlightResource::class, AdmissionStepResource::class,
            AdmissionContactGroupResource::class, ResultBatchResource::class, AdmissionSubscriberResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}
