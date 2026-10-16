<?php

namespace Modules\Notice\Filament\Resources\Notices\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Notice\Filament\Resources\Notices\NoticeResource;
use Modules\Notice\Models\Notice;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('notice::notice.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Notice $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Notice $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
