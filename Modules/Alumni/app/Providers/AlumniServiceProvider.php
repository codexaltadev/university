<?php

namespace Modules\Alumni\Providers;

use Livewire\Livewire;
use Modules\Alumni\Livewire\SubmitForm;
use Modules\Alumni\Support\AlumniWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class AlumniServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Alumni';

    protected string $nameLower = 'alumni';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(AlumniWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('alumni.submit', SubmitForm::class);   // FR-ALUM-07

        // Website: /alumni, /alumni/submit, /alumni/{slug}, /{department|program}/alumni (FR-ALUM-02/03/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(AlumniWebsite::class)->respond($rest, $context, $locale));
    }
}
