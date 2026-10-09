<?php

namespace Modules\Media\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Media\Filament\Resources\MediaItems\MediaItemResource;
use Modules\Media\Policies\MediaItemPolicy;
use Platform\Media\MediaItem;
use Platform\Modules\ModuleServiceProvider;

class MediaServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Media';

    protected string $nameLower = 'media';

    public function register(): void
    {
        parent::register();

        // SRS C-07: view, upload (create), update, delete, trash; "manage all" is the custom ManageAll:MediaItem
        config(['filament-shield.resources.manage.'.MediaItemResource::class => [
            'viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete',
        ]]);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(MediaItem::class, MediaItemPolicy::class);

        Route::middleware('web')->group(module_path($this->name, 'routes/web.php'));
    }
}
