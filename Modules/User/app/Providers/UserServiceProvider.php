<?php

namespace Modules\User\Providers;

use App\Models\User;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Modules\User\Http\Middleware\EnsureUserIsActive;
use Modules\User\Listeners\RecordAuthenticationEvents;
use Modules\User\Policies\UserPolicy;
use Platform\Modules\ModuleServiceProvider;

class UserServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'User';

    protected string $nameLower = 'user';

    public function boot(): void
    {
        parent::boot();

        // App\Models\User lives in the kernel; its policy belongs to this module
        Gate::policy(User::class, UserPolicy::class);

        Event::subscribe(RecordAuthenticationEvents::class);

        // Runs before Filament's Authenticate (which would answer 403), after the session starts
        $this->app->make(Kernel::class)->addToMiddlewarePriorityBefore(AuthenticatesRequests::class, EnsureUserIsActive::class);
    }
}
