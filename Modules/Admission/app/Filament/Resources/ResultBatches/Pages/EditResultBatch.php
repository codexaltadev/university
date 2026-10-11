<?php

namespace Modules\Admission\Filament\Resources\ResultBatches\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Admission\Filament\Resources\ResultBatches\ResultBatchResource;
use Modules\Admission\Models\AdmissionResult;
use Modules\Admission\Models\ResultBatch;
use Modules\Admission\Support\ResultImporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A result batch: Import (FR-ADM-05 — "Check only" previews the file and its errors without
 * saving; importing replaces the batch's results) and Export (FR-ADM-12).
 */
class EditResultBatch extends EditRecord
{
    protected static string $resource = ResultBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')->label(__('admission::admission.import.action'))->icon(Heroicon::OutlinedArrowUpTray)
                ->authorize(fn (ResultBatch $record): bool => (bool) auth()->user()?->can('update', $record))
                ->modalDescription(__('admission::admission.import.help'))
                ->schema([
                    FileUpload::make('file')->label(__('admission::admission.import.file'))->required()
                        ->disk('local')->directory('admission-imports')->preserveFilenames()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(20480),
                    Toggle::make('check_only')->label(__('admission::admission.import.check_only'))->default(true)
                        ->helperText(__('admission::admission.import.check_only_help')),
                ])
                ->action(fn (array $data, ResultBatch $record) => $this->import($record, (string) $data['file'], (bool) $data['check_only'])),
            Action::make('export')->label(__('admission::admission.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (ResultBatch $record): StreamedResponse => $this->export($record)),
            DeleteAction::make(),
        ];
    }

    protected function import(ResultBatch $batch, string $file, bool $checkOnly): void
    {
        $importer = app(ResultImporter::class);
        $path = Storage::disk('local')->path($file);
        $checked = $importer->check($path);
        $errors = collect($checked['errors']);

        if (! $checkOnly && $checked['rows'] !== []) {
            $importer->save($batch, $checked['rows']);
        }
        Storage::disk('local')->delete($file);
        $this->refreshFormData(['imported_rows', 'imported_at']);

        $summary = __($checkOnly ? 'admission::admission.import.checked' : 'admission::admission.import.done', [
            'good' => count($checked['rows']), 'total' => $checked['total'], 'bad' => $errors->count(),
        ]);

        Notification::make()
            ->{$errors->isEmpty() ? 'success' : 'warning'}()
            ->title($summary)
            ->body($errors->take(15)->implode("\n").($errors->count() > 15 ? "\n…" : ''))
            ->persistent($errors->isNotEmpty())
            ->send();
    }

    protected function export(ResultBatch $batch): StreamedResponse
    {
        return response()->streamDownload(function () use ($batch): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Program', 'Application serial', 'Student name', 'Result']);
            $batch->results()->orderBy('application_serial')->chunk(1000, function ($results) use ($out): void {
                foreach ($results as $result) {
                    /** @var AdmissionResult $result */
                    fputcsv($out, [$result->program_label, $result->application_serial, $result->student_name, $result->result_label ?: $result->result]);
                }
            });
            fclose($out);
        }, 'admission-results-'.$batch->id.'.csv', ['Content-Type' => 'text/csv']);
    }
}
