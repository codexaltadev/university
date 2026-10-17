<?php

namespace Modules\FacultyMember\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\FacultyMember\Filament\Resources\FacultyMembers\FacultyMemberResource;

class FacultyMemberPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'faculty-member';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([FacultyMemberResource::class]);
    }

    public function boot(Panel $panel): void {}
}
