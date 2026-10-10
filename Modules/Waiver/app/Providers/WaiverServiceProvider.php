<?php

namespace Modules\Waiver\Providers;

use Livewire\Livewire;
use Modules\Waiver\Livewire\WaiverCalculatorForm;
use Modules\Waiver\Support\CalculatorWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class WaiverServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Waiver';

    protected string $nameLower = 'waiver';

    public function boot(): void
    {
        parent::boot();

        Livewire::component('waiver.calculator', WaiverCalculatorForm::class);   // FR-FEE-06

        // Website: /waiver-calculator (FR-FEE-06)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => CalculatorWebsite::respond($rest, $context));
    }
}
