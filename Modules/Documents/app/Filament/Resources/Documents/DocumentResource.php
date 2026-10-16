<?php

namespace Modules\Documents\Filament\Resources\Documents;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Documents\Filament\Resources\Documents\Pages\CreateDocument;
use Modules\Documents\Filament\Resources\Documents\Pages\EditDocument;
use Modules\Documents\Filament\Resources\Documents\Pages\ListDocuments;
use Modules\Documents\Models\Document;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Content › Documents (M-27, FR-DOC-01..08): type (its own fields — policy: version, approval
 * date; prospectus: session, program — AC-DOC-01), title and description per language, the file
 * from the Media Library or an outside address, cover; flipbook, read-only, private; right:
 * publishing, address, Show in, highlight, category, SEO. "New version" on the edit page.
 */
class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentArrowDown;

    protected static ?int $navigationSort = 18;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('documents::documents.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('documents::documents.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(Document::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("documents::documents.types.{$t}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $program = 'Modules\\Program\\Models\\Program';
        $is = fn (string $type): \Closure => fn (Get $get): bool => $get('document_type') === $type;

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        Select::make('document_type')->label(__('documents::documents.fields.type'))->options(self::typeOptions())
                            ->default('other')->selectablePlaceholder(false)->required()->live(),
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("title.{$locale}")->label(__('documents::documents.fields.title'))->required($isDefault)->maxLength(255),
                            Textarea::make("description.{$locale}")->label(__('documents::documents.fields.description'))->rows(3)->maxLength(2000),
                        ]),
                        // Policy (AC-DOC-01)
                        Grid::make(3)->visible($is('policy'))->schema([
                            TextInput::make('version_label')->label(__('documents::documents.fields.version_label'))->placeholder('v2.1')->maxLength(30),
                            DatePicker::make('approved_on')->label(__('documents::documents.fields.approved_on')),
                            DatePicker::make('effective_on')->label(__('documents::documents.fields.effective_on')),
                        ]),
                        // Prospectus (AC-DOC-01)
                        Grid::make(2)->visible($is('prospectus'))->schema([
                            TextInput::make('session_label')->label(__('documents::documents.fields.session_label'))->placeholder('Fall 2026')->maxLength(50),
                            Select::make('program_id')->label(__('documents::documents.fields.program'))->searchable()
                                ->visible(fn (): bool => class_exists($program))
                                ->getSearchResultsUsing(fn (string $search): array => $program::query()->where('name', 'like', "%{$search}%")->limit(30)->get()
                                    ->mapWithKeys(fn ($p): array => [$p->id => (string) $p->name])->all())
                                ->getOptionLabelUsing(fn ($value): ?string => $program::query()->find($value)?->name),
                        ]),
                    ]),
                    Section::make(__('documents::documents.sections.file'))->compact()->schema([
                        MediaPicker::make('file')->label(__('documents::documents.fields.file'))->helperText(__('documents::documents.help.file')),
                        TextInput::make('external_url')->label(__('documents::documents.fields.external_url'))->helperText(__('documents::documents.help.external_url'))
                            ->url()->maxLength(2048),
                        MediaPicker::make('thumbnail')->label(__('documents::documents.fields.thumbnail'))->helperText(__('documents::documents.help.thumbnail'))->image(),
                        Grid::make(3)->schema([
                            Toggle::make('show_as_flipbook')->label(__('documents::documents.fields.show_as_flipbook'))->helperText(__('documents::documents.help.show_as_flipbook'))->inline(false),
                            Toggle::make('allow_download')->label(__('documents::documents.fields.allow_download'))->helperText(__('documents::documents.help.allow_download'))->default(true)->inline(false),
                            Toggle::make('is_private')->label(__('documents::documents.fields.is_private'))->helperText(__('documents::documents.help.is_private'))->inline(false),
                        ]),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Document::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Document::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms', 'supersededBy']))
            ->defaultSort('active_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('documents::documents.fields.title'))->searchable()->wrap()->limit(80)
                    ->description(fn (Document $record): ?string => collect([$record->version_label, $record->session_label, $record->supersededBy ? __('documents::documents.superseded') : null])->filter()->implode(' · ') ?: null),
                TextColumn::make('document_type')->label(__('documents::documents.fields.type'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("documents::documents.types.{$state}")),
                IconColumn::make('show_as_flipbook')->label(__('documents::documents.flipbook.short'))->boolean()->toggleable(),
                IconColumn::make('is_private')->label(__('documents::documents.fields.is_private'))->boolean()->toggleable(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Document $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                TextColumn::make('download_count')->label(__('documents::documents.downloads'))->numeric()->sortable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('document_type')->label(__('documents::documents.fields.type'))->options(self::typeOptions()),
                TernaryFilter::make('current')->label(__('documents::documents.current'))
                    ->queries(true: fn (Builder $query) => $query->current(), false: fn (Builder $query) => $query->whereNotNull('superseded_by_id'), blank: fn (Builder $query) => $query),
                ContentFilters::shownIn(),
                ContentFilters::term('category', Document::CATEGORY),
                ContentFilters::mainSite(),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocuments::route('/'),
            'create' => CreateDocument::route('/create'),
            'edit' => EditDocument::route('/{record}/edit'),
        ];
    }
}
