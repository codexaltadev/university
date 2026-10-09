<?php

namespace Modules\ModuleManager\Filament\Resources\Modules\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\ModuleManager\Services\ModuleOverview;
use Platform\Models\CoreModule;
use Platform\Models\ModuleOperation;

/** Module details (FR-MODM-12): README, permissions, dependencies, version history, install log. */
class ModuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $overview = fn (): ModuleOverview => app(ModuleOverview::class);

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make(__('modulemanager::manager.sections.about'))->columnSpan(2)->schema([
                    TextEntry::make('readme')->hiddenLabel()
                        ->state(fn (CoreModule $record): string => $overview()->readme($record) ?: __('modulemanager::manager.no_readme'))
                        ->markdown(),
                ]),
                Section::make(__('modulemanager::manager.sections.facts'))->columnSpan(1)->schema([
                    TextEntry::make('slug')->label(__('modulemanager::manager.fields.slug')),
                    TextEntry::make('version')->label(__('modulemanager::manager.fields.version')),
                    TextEntry::make('type')->label(__('modulemanager::manager.fields.type'))->badge(),
                    TextEntry::make('source_path')->label(__('modulemanager::manager.fields.path')),
                    TextEntry::make('author')->label(__('modulemanager::manager.fields.author'))
                        ->state(fn (CoreModule $record): string => $record->manifest['uwms']['author']['name'] ?? '—'),
                    KeyValueEntry::make('depends_on')->label(__('modulemanager::manager.fields.depends_on'))
                        ->state(fn (CoreModule $record): array => $overview()->dependsOn($record))
                        ->placeholder('—'),
                    TextEntry::make('used_by')->label(__('modulemanager::manager.fields.used_by'))
                        ->state(fn (CoreModule $record): array => $overview()->usedBy($record))
                        ->badge()
                        ->placeholder('—'),
                ]),
            ]),

            Section::make(__('modulemanager::manager.sections.permissions'))->collapsible()->columnSpanFull()->schema([
                TextEntry::make('permissions')->hiddenLabel()
                    ->state(fn (CoreModule $record): array => $overview()->permissions($record))
                    ->badge()
                    ->placeholder(__('modulemanager::manager.none')),
            ]),

            Section::make(__('modulemanager::manager.sections.versions'))->collapsible()->columnSpanFull()->schema([
                RepeatableEntry::make('versions')->hiddenLabel()
                    ->schema([
                        TextEntry::make('to_version')->label(__('modulemanager::manager.fields.version')),
                        TextEntry::make('from_version')->label(__('modulemanager::manager.fields.from'))->placeholder('—'),
                        TextEntry::make('created_at')->label(__('modulemanager::manager.fields.when'))->dateTime(),
                    ])
                    ->columns(3)
                    ->placeholder(__('modulemanager::manager.none')),
            ]),

            Section::make(__('modulemanager::manager.sections.operations'))->collapsible()->columnSpanFull()->schema([
                RepeatableEntry::make('operations')->hiddenLabel()
                    ->state(fn (CoreModule $record) => ModuleOperation::query()->where('module_slug', $record->slug)->latest('id')->limit(20)->get())
                    ->schema([
                        TextEntry::make('operation')->label(__('modulemanager::manager.fields.operation'))->badge(),
                        TextEntry::make('status')->label(__('modulemanager::manager.fields.status'))->badge()
                            ->color(fn (string $state): string => $state === 'success' ? 'success' : ($state === 'running' ? 'gray' : 'danger')),
                        TextEntry::make('started_at')->label(__('modulemanager::manager.fields.when'))->dateTime(),
                        TextEntry::make('error')->label(__('modulemanager::manager.fields.error'))->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->placeholder(__('modulemanager::manager.none')),
            ]),
        ]);
    }
}
