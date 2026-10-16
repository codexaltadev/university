<?php

namespace Modules\Testimonials\Providers;

use Modules\Testimonials\Support\TestimonialsWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class TestimonialsServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Testimonials';

    protected string $nameLower = 'testimonials';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(TestimonialsWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /testimonials, also under a microsite (FR-TEST-02)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(TestimonialsWebsite::class)->respond($rest, $context, $locale));
    }
}
