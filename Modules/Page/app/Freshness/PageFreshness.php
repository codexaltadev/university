<?php

namespace Modules\Page\Freshness;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Modules\Page\Models\Page;
use Platform\Notifications\FreshnessSource;

/** Content Update Reminder (FR-NOTI-10): an entity's own pages (owner), by last change. */
class PageFreshness extends FreshnessSource
{
    public static function module(): string
    {
        return 'page';
    }

    public static function model(): string
    {
        return Page::class;
    }

    public static function label(): string
    {
        return __('page::page.search_group');
    }

    public static function link(): string
    {
        return 'owner';
    }

    public function editUrl(Model $record): ?string
    {
        return Route::has('filament.admin.resources.pages.edit') ? route('filament.admin.resources.pages.edit', $record) : null;
    }
}
