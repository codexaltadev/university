<?php

namespace Modules\Gallery\Filament\Resources\Albums\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Gallery\Filament\Resources\Albums\AlbumResource;
use Modules\Gallery\Models\Album;

class ListAlbums extends ListRecords
{
    protected static string $resource = AlbumResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('gallery::gallery.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Album::PATH))->openUrlInNewTab(),
        ];
    }
}
