<?php

namespace Modules\Gallery\Filament\Resources\Albums\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Gallery\Filament\Resources\Albums\AlbumResource;
use Modules\Gallery\Models\Album;

class EditAlbum extends EditRecord
{
    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('gallery::gallery.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Album $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Album $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
