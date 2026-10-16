<?php

namespace Modules\Notice\Filament\Resources\Notices\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Notice\Filament\Resources\Notices\NoticeResource;
use Modules\Notice\Models\Notice;

class ListNotices extends ListRecords
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('notice::notice.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Notice::PATH))->openUrlInNewTab(),
        ];
    }
}
