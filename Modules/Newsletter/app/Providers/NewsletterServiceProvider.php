<?php

namespace Modules\Newsletter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Modules\Newsletter\Console\DispatchCampaignsCommand;
use Modules\Newsletter\Livewire\SubscribeForm;
use Modules\Newsletter\Models\Delivery;
use Modules\Newsletter\Support\NewsletterWebsite;
use Modules\Newsletter\Support\Subscriptions;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class NewsletterServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Newsletter';

    protected string $nameLower = 'newsletter';

    public const ADMISSION_SUBSCRIBER = 'Modules\\Admission\\Models\\AdmissionSubscriber';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(NewsletterWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(NewsletterWebsite::class)->respond($rest, $context, $locale));
        Livewire::component('newsletter.subscribe', SubscribeForm::class);                              // FR-NLT-01
        if (! $this->app->routesAreCached()) {
            Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));                  // confirm / unsubscribe links
        }
        $this->commands([DispatchCampaignsCommand::class]);
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('newsletter:dispatch')->everyMinute()->withoutOverlapping();               // FR-NLT-05: scheduled campaigns
            $schedule->command('model:prune', ['--model' => [Delivery::class]])->dailyAt('03:40');       // deliveries after 90 days
        });

        // FR-NLT-08: Admission's confirmed email subscribers → "Admission Updates" (Admission is optional)
        if (class_exists(self::ADMISSION_SUBSCRIBER)) {
            (self::ADMISSION_SUBSCRIBER)::saved(fn ($subscriber) => $this->app->make(Subscriptions::class)->syncAdmission($subscriber));
        }
    }
}
