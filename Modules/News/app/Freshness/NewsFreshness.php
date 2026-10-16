<?php

namespace Modules\News\Freshness;

use Illuminate\Database\Eloquent\Model;
use Modules\News\Filament\Resources\Posts\PostResource;
use Modules\News\Models\Post;
use Platform\Notifications\FreshnessSource;

/** Content Update Reminder (FR-NOTI-10): an entity's news goes stale when nothing new was shown there for a while. */
class NewsFreshness extends FreshnessSource
{
    public static function module(): string
    {
        return 'news';
    }

    public static function model(): string
    {
        return Post::class;
    }

    public static function label(): string
    {
        return __('news::news.plural');
    }

    public function editUrl(Model $record): ?string
    {
        return PostResource::getUrl('edit', ['record' => $record]);
    }
}
