<?php

namespace Modules\Admission\Filament\Resources\Seats\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Filament\Resources\Seats\AdmissionSeatResource;
use Modules\Admission\Support\SeatImporter;

/** The seat plan: Upload (checked first; saving replaces the whole list), add or correct one seat. */
class ManageAdmissionSeats extends ManageRecords
{
    protected static string $resource = AdmissionSeatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')->label(__('admission::admission.seats.import'))->icon(Heroicon::OutlinedArrowUpTray)
                ->authorize(fn (): bool => (bool) auth()->user()?->can('create', AdmissionSeatResource::getModel()))
                ->modalDescription(__('admission::admission.seats.import_help'))
                ->schema([
                    FileUpload::make('file')->label(__('admission::admission.import.file'))->required()
                        ->disk('local')->directory('admission-imports')->preserveFilenames()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->maxSize(10240),
                    Toggle::make('check_only')->label(__('admission::admission.import.check_only'))->default(true)
                        ->helperText(__('admission::admission.import.check_only_help')),
                ])
                ->action(fn (array $data) => $this->import((string) $data['file'], (bool) $data['check_only'])),
            CreateAction::make(),
        ];
    }

    protected function import(string $file, bool $checkOnly): void
    {
        $importer = app(SeatImporter::class);
        $checked = $importer->check(Storage::disk('local')->path($file));
        $errors = collect($checked['errors']);

        if (! $checkOnly && $checked['rows'] !== []) {
            $importer->save($checked['rows']);
        }
        Storage::disk('local')->delete($file);

        Notification::make()
            ->{$errors->isEmpty() ? 'success' : 'warning'}()
            ->title(__($checkOnly ? 'admission::admission.import.checked' : 'admission::admission.import.done', [
                'good' => count($checked['rows']), 'total' => $checked['total'], 'bad' => $errors->count(),
            ]))
            ->body($errors->take(15)->implode("\n").($errors->count() > 15 ? "\n…" : ''))
            ->persistent($errors->isNotEmpty())
            ->send();
    }
}
