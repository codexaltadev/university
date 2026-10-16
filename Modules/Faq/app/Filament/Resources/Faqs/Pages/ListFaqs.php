<?php

namespace Modules\Faq\Filament\Resources\Faqs\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Modules\Faq\Filament\Resources\Faqs\FaqResource;
use Modules\Faq\Models\Faq;
use Modules\Faq\Support\FaqImporter;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;

/** FAQ list with Import (FR-FAQ-06): check first, then save where the importer may show them. */
class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('import')->label(__('faq::faq.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
                ->authorize(fn (): bool => (bool) auth()->user()?->can('create', Faq::class))
                ->modalDescription(__('faq::faq.import.help'))
                ->schema([
                    FileUpload::make('file')->label(__('faq::faq.import.file'))->required()
                        ->disk('local')->directory('faq-imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->maxSize(10240),
                    Select::make('show_in')->label(__('platform::relations.show_in'))->multiple()
                        ->options(fn (): array => app(Targets::class)->options(auth()->user()))
                        ->default(fn (): array => app(Targets::class)->defaultFor(auth()->user())),
                    Toggle::make('main_site')->label(__('platform::relations.main_site'))
                        ->visible(fn (): bool => app(RelationEngine::class)->mayPublishMain(auth()->user(), Faq::class)),
                    Toggle::make('check_only')->label(__('faq::faq.import.check_only'))->default(true),
                ])
                ->action(function (array $data): void {
                    $importer = app(FaqImporter::class);
                    $file = (string) $data['file'];
                    $checked = $importer->check(Storage::disk('local')->path($file));
                    $allowed = collect(app(Targets::class)->options(auth()->user()))->flatMap(fn ($v, $k) => is_array($v) ? $v : [$k => $v])->keys()->all();
                    $targets = array_values(array_intersect((array) ($data['show_in'] ?? []), $allowed));   // only places the user may show content in

                    if (! $data['check_only'] && $checked['rows'] !== []) {
                        $importer->save($checked['rows'], $targets, (bool) ($data['main_site'] ?? false));
                    }
                    Storage::disk('local')->delete($file);

                    Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                        ->title(__($data['check_only'] ? 'faq::faq.import.checked' : 'faq::faq.import.done', ['good' => count($checked['rows']), 'total' => $checked['total']]))
                        ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                        ->persistent($checked['errors'] !== [])
                        ->send();
                }),
        ];
    }
}
