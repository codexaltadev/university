<?php

namespace Modules\Seo\Filament\Resources\Redirects\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Modules\Seo\Filament\Resources\Redirects\RedirectResource;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Support\Redirects;

class ListRedirects extends ListRecords
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // FR-SEO-05: the old website's address map
            Action::make('import')->label(__('seo::seo.actions.import'))
                ->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->visible(fn (): bool => (bool) auth()->user()?->can('create', Redirect::class))
                ->modalDescription(__('seo::seo.help.import'))
                ->schema([
                    FileUpload::make('file')->label(__('seo::seo.fields.csv'))
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(5 * 1024)->required()->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'];
                    $path = $file instanceof TemporaryUploadedFile ? $file->getRealPath() : Storage::disk('local')->path((string) $file);
                    $result = app(Redirects::class)->import($path);

                    Notification::make()
                        ->title(__('seo::seo.import.done', ['created' => $result['created'], 'updated' => $result['updated'], 'skipped' => count($result['skipped'])]))
                        ->body($result['skipped'] ? implode('<br>', array_map('e', array_slice($result['skipped'], 0, 20))) : null)
                        ->{$result['skipped'] ? 'warning' : 'success'}()
                        ->persistent((bool) $result['skipped'])
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
