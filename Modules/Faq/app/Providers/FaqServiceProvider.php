<?php

namespace Modules\Faq\Providers;

use Modules\Faq\Support\FaqWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class FaqServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Faq';

    protected string $nameLower = 'faq';

    /** @var string[] */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,   // "Was this helpful?" (routes/web.php)
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(FaqWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /faq and /{entity}/faq (FR-FAQ-02/03)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(FaqWebsite::class)->respond($rest, $context, $locale));
    }
}
