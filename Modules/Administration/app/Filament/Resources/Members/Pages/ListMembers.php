<?php

namespace Modules\Administration\Filament\Resources\Members\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Administration\Filament\Resources\Members\MemberResource;
use Modules\Administration\Models\Member;
use Modules\Administration\Support\MemberSheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Members with Import and Export (FR-ADMN-06): the same columns both ways, import checked first. */
class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')->label(__('administration::administration.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->authorize(fn (): bool => (bool) auth()->user()?->can('create', Member::class))
                ->modalDescription(__('administration::administration.import.help', ['columns' => implode(', ', MemberSheet::COLUMNS)]))
                ->schema([
                    FileUpload::make('file')->label(__('administration::administration.import.file'))->required()
                        ->disk('local')->directory('administration-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(10240),
                    Toggle::make('check_only')->label(__('administration::administration.import.check_only'))->default(true),
                ])
                ->action(function (array $data): void {
                    $sheet = app(MemberSheet::class);
                    $file = (string) $data['file'];
                    $checked = $sheet->check(Storage::disk('local')->path($file));
                    $saved = ! $data['check_only'] && $checked['rows'] !== [] ? $sheet->save($checked['rows']) : null;
                    Storage::disk('local')->delete($file);

                    Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                        ->title($saved
                            ? __('administration::administration.import.done', $saved + ['total' => $checked['total']])
                            : __('administration::administration.import.checked', ['good' => count($checked['rows']), 'total' => $checked['total']]))
                        ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                        ->persistent($checked['errors'] !== [])
                        ->send();
                }),
            Action::make('export')->label(__('administration::administration.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    $out = fopen('php://output', 'w');
                    app(MemberSheet::class)->export($out);
                    fclose($out);
                }, 'administration.csv', ['Content-Type' => 'text/csv'])),
            Action::make('website')->label(__('administration::administration.view_site'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(url('/'.Member::PATH))->openUrlInNewTab(),
        ];
    }
}
