<?php

namespace Modules\Events\Filament\Resources\Events;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
use Modules\Events\Filament\Resources\Events\Pages\CreateEvent;
use Modules\Events\Filament\Resources\Events\Pages\EditEvent;
use Modules\Events\Filament\Resources\Events\Pages\ListEvents;
use Modules\Events\Models\Event;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
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
 * Content › Events (M-19, FR-EVT-01..10): texts, venue and organizer per language; dates (start
 * required, end after it), all day; online link; registration with a closing time; picture and
 * gallery; speakers. Right: publishing, address, Show in, highlight, category, SEO.
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('events::events.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('events::events.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("title.{$locale}")->label(__('events::events.fields.title'))->required($isDefault)->maxLength(255),
                            Textarea::make("summary.{$locale}")->label(__('events::events.fields.summary'))->rows(2)->maxLength(500),
                            RichEditor::make("body.{$locale}")->label(__('events::events.fields.body'))->fileAttachments(false),
                            Grid::make(2)->schema([
                                TextInput::make("venue.{$locale}")->label(__('events::events.fields.venue'))
                                    ->helperText($isDefault ? __('events::events.help.venue') : null)->maxLength(255),
                                TextInput::make("organizer_label.{$locale}")->label(__('events::events.fields.organizer'))->maxLength(191),
                            ]),
                        ]),
                    ]),
                    Section::make(__('events::events.sections.when'))->compact()->schema([
                        Grid::make(3)->schema([
                            DateTimePicker::make('starts_at')->label(__('events::events.fields.starts_at'))->required()->seconds(false),
                            DateTimePicker::make('ends_at')->label(__('events::events.fields.ends_at'))->seconds(false)->afterOrEqual('starts_at'),
                            Toggle::make('is_all_day')->label(__('events::events.fields.is_all_day'))->inline(false),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('online_url')->label(__('events::events.fields.online_url'))->url()->maxLength(2048),
                            TextInput::make('registration_url')->label(__('events::events.fields.registration_url'))->url()->maxLength(2048),
                            DateTimePicker::make('registration_ends_at')->label(__('events::events.fields.registration_ends_at'))->seconds(false),
                        ]),
                    ]),
                    Section::make(__('events::events.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('featured_image')->label(__('events::events.fields.featured_image'))->image(),
                        MediaPicker::make('gallery')->label(__('events::events.fields.gallery'))->image()->multiple()
                            ->helperText(__('events::events.help.gallery')),
                    ]),
                    PeopleManager::make()->heading(__('events::events.sections.speakers')),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Event::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Event::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort('starts_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('events::events.fields.title'))->searchable()->wrap()->limit(90),
                TextColumn::make('starts_at')->label(__('events::events.fields.when'))->sortable()
                    ->formatStateUsing(fn (Event $record): string => $record->when()),
                TextColumn::make('state')->label('')->badge()
                    ->state(fn (Event $record): string => __('events::events.states.'.$record->state()))
                    ->color(fn (Event $record): string => match ($record->state()) {
                        'upcoming' => 'success', 'ongoing' => 'warning', default => 'gray',
                    }),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Event $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('when')->label(__('events::events.fields.when'))
                    ->options(['upcoming' => __('events::events.tabs.upcoming'), 'past' => __('events::events.tabs.past')])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'upcoming' => $query->upcoming(),
                        'past' => $query->past(),
                        default => $query,
                    }),
                ContentFilters::shownIn(),
                ContentFilters::term('category', Event::CATEGORY),
                ContentFilters::mainSite(),
                TernaryFilter::make('is_active')->label(__('platform::relations.active')),
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
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
