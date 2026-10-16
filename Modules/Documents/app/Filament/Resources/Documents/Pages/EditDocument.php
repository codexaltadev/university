<?php

namespace Modules\Documents\Filament\Resources\Documents\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Documents\Filament\Resources\Documents\DocumentResource;
use Modules\Documents\Models\Document;
use Modules\Documents\Support\Versions;
use Platform\Filament\Components\MediaPicker;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newVersion')->label(__('documents::documents.new_version'))->icon(Heroicon::OutlinedDocumentDuplicate)
                ->visible(fn (Document $record): bool => $record->superseded_by_id === null)
                ->modalDescription(__('documents::documents.help.new_version'))
                ->schema([
                    MediaPicker::make('file')->label(__('documents::documents.fields.file'))->storeIds()->required(),
                    TextInput::make('version_label')->label(__('documents::documents.fields.version_label'))->maxLength(30),
                    DatePicker::make('approved_on')->label(__('documents::documents.fields.approved_on')),
                    DatePicker::make('effective_on')->label(__('documents::documents.fields.effective_on')),
                ])
                ->action(function (array $data, Document $record) {
                    $new = app(Versions::class)->create($record, $data);

                    return redirect(DocumentResource::getUrl('edit', ['record' => $new]));
                }),
            Action::make('view')->label(__('documents::documents.view_page'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn (Document $record): string => url($record->url()))->openUrlInNewTab()
                ->visible(fn (Document $record): bool => $record->isFrontendVisible()),
            DeleteAction::make(),
        ];
    }
}
