<?php

namespace Modules\Admission\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Modules\Admission\Livewire\ResultLookup;
use Modules\Admission\Livewire\SubscribeForm;
use Modules\Admission\Support\AdmissionBlocks;
use Modules\Admission\Support\AdmissionPage;
use Modules\Admission\Support\AdmissionWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class AdmissionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Admission';

    protected string $nameLower = 'admission';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AdmissionWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('admission.result-lookup', ResultLookup::class);   // FR-ADM-06
        Livewire::component('admission.subscribe', SubscribeForm::class);       // FR-ADM-09

        // What themes show (T-1): the blocks' circulars, links, cards, steps and contacts
        View::composer('admission::blocks.*', fn ($view) => $view->with('admission', new AdmissionBlocks($view->getData())));
        View::composer(['admission::result', 'admission::contact', 'admission::circulars', 'admission::circular'], fn ($view) => $view->with('page', new AdmissionPage($view->getData())));

        // Website: /admission/result, /admission/circulars[/{slug}] (FR-ADM-16); /admission itself is a page
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(AdmissionWebsite::class)->respond($rest, $context, $locale));

        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));   // subscription confirm / unsubscribe links
        }
    }
}
