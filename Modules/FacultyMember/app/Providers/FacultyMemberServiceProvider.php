<?php

namespace Modules\FacultyMember\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Modules\FacultyMember\Console\SyncFacultyMembersCommand;
use Modules\FacultyMember\Support\FacultyMemberWebsite;
use Platform\Context\SiteContext;
use Platform\Modules\ModuleServiceProvider;
use Platform\Website\Website;

class FacultyMemberServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'FacultyMember';

    protected string $nameLower = 'facultymember';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(FacultyMemberWebsite::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Website: /faculty-members, /faculty-members/{slug}, {department}/faculty-members (FR-FM-03/04/05)
        $this->app->make(Website::class)->handle(fn (string $rest, SiteContext $context, string $locale) => $this->app->make(FacultyMemberWebsite::class)->respond($rest, $context, $locale));

        $this->commands([SyncFacultyMembersCommand::class]);
        $this->app->booted(fn () => $this->app->make(Schedule::class)->command('faculty-member:sync')->dailyAt('05:30')->withoutOverlapping()
            ->when(fn (): bool => (bool) settings('faculty-member.auto_sync', true)));   // FR-FM-08
    }
}
