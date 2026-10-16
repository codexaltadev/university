<?php

namespace Modules\Testimonials\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Modules\Testimonials\Filament\Resources\Testimonials\TestimonialResource;

class TestimonialsPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'testimonials';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([TestimonialResource::class]);
    }

    public function boot(Panel $panel): void {}
}
