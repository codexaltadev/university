<?php

namespace Modules\Alumni\Filament\Resources\Alumni\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Alumni\Filament\Resources\Alumni\AlumnusResource;
use Modules\Alumni\Models\Alumnus;
use Modules\Alumni\Support\AlumniSheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Alumni with Import and Export (FR-ALUM-09): the same columns both ways, import checked first. */
class ListAlumni extends ListRecords
{
    protected static string $resource = AlumnusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')->label(__('alumni::alumni.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->authorize(fn (): bool => (bool) auth()->user()?->can('create', Alumnus::class))
                ->modalDescription(__('alumni::alumni.import.help', ['columns' => implode(', ', AlumniSheet::COLUMNS)]))
                ->schema([
                    FileUpload::make('file')->label(__('alumni::alumni.import.file'))->required()
                        ->disk('local')->directory('alumni-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(10240),
                    Toggle::make('check_only')->label(__('alumni::alumni.import.check_only'))->default(true),
                ])
                ->action(function (array $data): void {
                    $sheet = app(AlumniSheet::class);
                    $file = (string) $data['file'];
                    $checked = $sheet->check(Storage::disk('local')->path($file));
                    $saved = ! $data['check_only'] && $checked['rows'] !== [] ? $sheet->save($checked['rows']) : null;
                    Storage::disk('local')->delete($file);

                    Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                        ->title($saved
                            ? __('alumni::alumni.import.done', $saved + ['total' => $checked['total']])
                            : __('alumni::alumni.import.checked', ['good' => count($checked['rows']), 'total' => $checked['total']]))
                        ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                        ->persistent($checked['errors'] !== [])
                        ->send();
                }),
            Action::make('export')->label(__('alumni::alumni.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    app(AlumniSheet::class)->export($out);
                    fclose($out);
                }, 'alumni.csv', ['Content-Type' => 'text/csv'])),
            Action::make('website')->label(__('alumni::alumni.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Alumnus::PATH))->openUrlInNewTab(),
        ];
    }
}
