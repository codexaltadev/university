<?php

namespace Modules\Newsletter\Filament\Resources\Subscribers\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Support\SubscriberImporter;
use Modules\Newsletter\Support\Subscriptions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [$this->exportAction(), $this->importAction(), CreateAction::make()];
    }

    /** FR-NLT-04: CSV / Excel — checked first; unsubscribed emails stay unsubscribed (BRL-NLT-02). */
    protected function importAction(): Action
    {
        return Action::make('import')->label(__('newsletter::newsletter.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->visible(fn (): bool => (bool) auth()->user()?->can('create', Subscriber::class))
            ->modalDescription(__('newsletter::newsletter.import.help'))
            ->schema([
                FileUpload::make('file')->label(__('newsletter::newsletter.import.file'))->required()->disk('local')->directory('newsletter-imports')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->maxSize(10240),
                Select::make('lists')->label(__('newsletter::newsletter.lists'))->multiple()->options(fn (): array => SubscriberResource::listOptions())
                    ->default(fn (): array => [app(Subscriptions::class)->general()->id])->required(),
                Toggle::make('check_only')->label(__('newsletter::newsletter.import.check_only'))->default(true),
            ])
            ->action(function (array $data): void {
                $importer = app(SubscriberImporter::class);
                $file = (string) $data['file'];
                $checked = $importer->check(Storage::disk('local')->path($file));
                $saved = ['added' => 0, 'kept' => 0];
                if (! $data['check_only'] && $checked['rows'] !== []) {
                    $saved = $importer->save($checked['rows'], array_map('intval', $data['lists']));
                }
                Storage::disk('local')->delete($file);

                Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                    ->title($data['check_only']
                        ? __('newsletter::newsletter.import.checked', ['good' => count($checked['rows']), 'total' => $checked['total']])
                        : __('newsletter::newsletter.import.done', ['added' => $saved['added'], 'kept' => $saved['kept'], 'total' => $checked['total']]))
                    ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                    ->persistent($checked['errors'] !== [])
                    ->send();
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('export')->label(__('newsletter::newsletter.export'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
            ->visible(fn (): bool => (bool) auth()->user()?->can('viewAny', Subscriber::class))
            ->schema([Select::make('list')->label(__('newsletter::newsletter.list'))->options(fn (): array => SubscriberResource::listOptions())->placeholder(__('newsletter::newsletter.all_lists'))])
            ->action(function (array $data): BinaryFileResponse {
                $path = storage_path('app/private/newsletter-subscribers-'.now()->format('Ymd-His').'.csv');
                @mkdir(dirname($path), 0775, true);
                app(SubscriberImporter::class)->export($path, filled($data['list'] ?? null) ? (int) $data['list'] : null);

                return response()->download($path)->deleteFileAfterSend();
            });
    }
}
