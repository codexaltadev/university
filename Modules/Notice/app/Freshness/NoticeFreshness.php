<?php

namespace Modules\Notice\Freshness;

use Illuminate\Database\Eloquent\Model;
use Modules\Notice\Filament\Resources\Notices\NoticeResource;
use Modules\Notice\Models\Notice;
use Platform\Notifications\FreshnessSource;

/** Content Update Reminder (FR-NOTI-10): an entity's notices go stale when nothing new was posted there for a while. */
class NoticeFreshness extends FreshnessSource
{
    public static function module(): string
    {
        return 'notice';
    }

    public static function model(): string
    {
        return Notice::class;
    }

    public static function label(): string
    {
        return __('notice::notice.plural');
    }

    public function editUrl(Model $record): ?string
    {
        return NoticeResource::getUrl('edit', ['record' => $record]);
    }
}
