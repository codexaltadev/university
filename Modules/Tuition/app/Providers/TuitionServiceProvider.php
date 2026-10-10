<?php

namespace Modules\Tuition\Providers;

use Modules\Program\Models\Program;
use Modules\Tuition\Models\Tuition;
use Modules\Tuition\Support\TuitionWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class TuitionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tuition';

    protected string $nameLower = 'tuition';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(TuitionWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // A Program's tuitions — several per program (one per category, more when the label differs).
        // Added here: the Program module does not know this one (Program Hub, FR-GEN-86)
        Program::resolveRelationUsing('tuitions', fn (Program $program) => $program->hasMany(Tuition::class));

        // Website: /tuition-fees (FR-FEE-03)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(TuitionWebsite::class)->respond($rest, $context, $locale));
    }
}
