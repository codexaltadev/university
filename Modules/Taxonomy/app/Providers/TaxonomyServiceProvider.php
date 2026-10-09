<?php

namespace Modules\Taxonomy\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Taxonomy\Filament\Resources\Vocabularies\VocabularyResource;
use Modules\Taxonomy\Policies\TermPolicy;
use Modules\Taxonomy\Policies\VocabularyPolicy;
use Platform\Modules\ModuleServiceProvider;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabulary;

class TaxonomyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Taxonomy';

    protected string $nameLower = 'taxonomy';

    public function register(): void
    {
        parent::register();

        // One custom permission (Manage:Taxonomy) instead of per-resource ones
        config()->push('filament-shield.resources.exclude', VocabularyResource::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Vocabulary::class, VocabularyPolicy::class);
        Gate::policy(Term::class, TermPolicy::class);
    }
}
