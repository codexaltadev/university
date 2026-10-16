<?php

namespace Modules\Gallery\Filament\Resources\Albums\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Gallery\Filament\Resources\Albums\AlbumResource;

class CreateAlbum extends CreateRecord
{
    protected static string $resource = AlbumResource::class;

    /** Link videos are added on the edit page. */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
