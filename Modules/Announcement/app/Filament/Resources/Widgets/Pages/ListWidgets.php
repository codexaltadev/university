<?php

namespace Modules\Announcement\Filament\Resources\Widgets\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Announcement\Filament\Resources\Widgets\WidgetResource;
use Modules\Announcement\Support\Announcements;

class ListWidgets extends ListRecords
{
    protected static string $resource = WidgetResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    /** The kinds still off in Settings › Announcements, which the website does not show */
    public function getSubheading(): ?string
    {
        $off = Announcements::disabled();

        return $off === [] ? null : __('announcement::announcement.settings.off_list', [
            'types' => collect($off)->map(fn (string $type): string => __("announcement::announcement.types.{$type}"))->implode(', '),
        ]);
    }
}
