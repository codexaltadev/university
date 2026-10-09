<?php

namespace Modules\Media\Filament\Resources\MediaItems\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Media\Filament\Resources\MediaItems\MediaActions;
use Modules\Media\Filament\Resources\MediaItems\MediaItemResource;
use Platform\Media\MediaItem;

/** Details of one file: preview, facts, link, texts per language, where it is used (FR-MEDIA-06/09). */
class EditMediaItem extends EditRecord
{
    protected static string $resource = MediaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MediaActions::download(),
            MediaActions::replace(),
            DeleteAction::make()->modalDescription(fn (MediaItem $record) => MediaActions::deleteWarning($record)),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
