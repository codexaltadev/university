<?php

namespace Modules\International\Filament\Resources\Institutions\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\International\Filament\Resources\Institutions\InstitutionResource;
use Modules\International\Models\Institution;
use Modules\International\Support\InstitutionImporter;

class ListInstitutions extends ListRecords
{
    protected static string $resource = InstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
            CreateAction::make(),
        ];
    }

    /** FR-INTL-07: Excel / CSV partner list — checked first, then saved. */
    protected function importAction(): Action
    {
        return Action::make('import')->label(__('international::international.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->visible(fn (): bool => (bool) auth()->user()?->can('create', Institution::class))
            ->modalDescription(__('international::international.import.help'))
            ->schema([
                FileUpload::make('file')->label(__('international::international.import.file'))->required()
                    ->disk('local')->directory('international-imports')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240),
                Toggle::make('check_only')->label(__('international::international.import.check_only'))->default(true),
            ])
            ->action(function (array $data): void {
                $importer = app(InstitutionImporter::class);
                $file = (string) $data['file'];
                $checked = $importer->check(Storage::disk('local')->path($file));
                if (! $data['check_only'] && $checked['rows'] !== []) {
                    $importer->save($checked['rows']);
                }
                Storage::disk('local')->delete($file);

                Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                    ->title(__($data['check_only'] ? 'international::international.import.checked' : 'international::international.import.done', ['good' => count($checked['rows']), 'total' => $checked['total']]))
                    ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                    ->persistent($checked['errors'] !== [])
                    ->send();
            });
    }
}
