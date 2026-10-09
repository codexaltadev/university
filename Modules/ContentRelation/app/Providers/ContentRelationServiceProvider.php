<?php

namespace Modules\ContentRelation\Providers;

use Modules\ContentRelation\Filament\Pages\Highlights;
use Modules\ContentRelation\Filament\Pages\MainSiteQueue;
use Platform\Modules\ModuleServiceProvider;

class ContentRelationServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'ContentRelation';

    protected string $nameLower = 'contentrelation';

    public function register(): void
    {
        parent::register();

        // Access follows PublishMain / HighlightMain of the content types, not a page permission
        config()->push('filament-shield.pages.exclude', MainSiteQueue::class);
        config()->push('filament-shield.pages.exclude', Highlights::class);
    }
}
