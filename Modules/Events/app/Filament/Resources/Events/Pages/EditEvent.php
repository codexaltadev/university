<?php

namespace Modules\Events\Filament\Resources\Events\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Events\Filament\Resources\Events\EventResource;
use Modules\Events\Models\Event;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('events::events.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Event $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Event $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
