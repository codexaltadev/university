<?php

namespace Modules\Fee\Providers;

use Livewire\Livewire;
use Modules\Fee\Livewire\WaiverCalculatorForm;
use Modules\Fee\Models\Tuition;
use Modules\Fee\Support\TuitionWebsite;
use Modules\Program\Models\Program;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class FeeServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Fee';

    protected string $nameLower = 'fee';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(TuitionWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::component('fee.waiver-calculator', WaiverCalculatorForm::class);   // FR-FEE-06

        // A Program's tuitions (Program Hub, FR-GEN-86) — added here, the Program module does not know this one
        Program::resolveRelationUsing('tuitions', fn (Program $program) => $program->hasMany(Tuition::class));

        // Website: /tuition-fees (FR-FEE-03), /waiver-calculator (FR-FEE-06)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(TuitionWebsite::class)->respond($rest, $context, $locale));
    }
}
