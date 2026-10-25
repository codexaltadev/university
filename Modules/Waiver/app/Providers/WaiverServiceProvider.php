<?php

namespace Modules\Waiver\Providers;

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Modules\Waiver\Livewire\WaiverCalculatorForm;
use Modules\Waiver\Support\CalculatorForm;
use Modules\Waiver\Support\CalculatorPage;
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

        // What themes show (T-1): the page around the form, and the form's figures
        View::composer('waiver::calculator.page', fn ($view) => $view->with('page', new CalculatorPage($view->getData())));
        View::composer('waiver::livewire.waiver-calculator', fn ($view) => $view->with('calc', new CalculatorForm($view->getData())));

        // Website: /waiver-calculator (FR-FEE-06)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => CalculatorWebsite::respond($rest, $context));
    }
}
