<?php

namespace Modules\Notice\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Notice\Filament\Resources\Notices\NoticeResource;

class NoticePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'notice';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([NoticeResource::class]);
    }

    public function boot(Panel $panel): void {}
}
