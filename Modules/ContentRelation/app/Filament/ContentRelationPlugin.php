<?php

namespace Modules\ContentRelation\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\ContentRelation\Filament\Pages\Highlights;
use Modules\ContentRelation\Filament\Pages\MainSiteQueue;

class ContentRelationPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'content-relation';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([MainSiteQueue::class, Highlights::class]);
    }

    public function boot(Panel $panel): void {}
}
