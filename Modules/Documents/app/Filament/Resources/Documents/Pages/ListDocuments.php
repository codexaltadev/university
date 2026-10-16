<?php

namespace Modules\Documents\Filament\Resources\Documents\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Documents\Filament\Resources\Documents\DocumentResource;
use Modules\Documents\Models\Document;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('website')->label(__('documents::documents.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Document::PATH))->openUrlInNewTab(),
        ];
    }
}
