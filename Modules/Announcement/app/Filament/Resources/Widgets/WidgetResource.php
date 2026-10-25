<?php

namespace Modules\Announcement\Filament\Resources\Widgets;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Announcement\Filament\Resources\Widgets\Pages\CreateWidget;
use Modules\Announcement\Filament\Resources\Widgets\Pages\EditWidget;
use Modules\Announcement\Filament\Resources\Widgets\Pages\ListWidgets;
use Modules\Announcement\Models\Widget;
use Modules\Announcement\Support\Announcements;
use Modules\Announcement\Support\CurrentPage;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Content › Announcements (M-30, FR-ANW-01..07): type (its own fields — popup: picture, text,
 * how often, priority; ticker: text, latest notices; countdown: target, after the end; bar:
 * colour, can be closed), link and button; which pages (homepage / all / chosen module pages);
 * right: active, from / until, Show in.
 */
class WidgetResource extends Resource
{
    protected static ?string $model = Widget::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('announcement::announcement.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('announcement::announcement.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function options(string $group, array $values): array
    {
        return collect($values)->mapWithKeys(fn (string $v): array => [$v => __("announcement::announcement.{$group}.{$v}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $is = fn (string ...$types): \Closure => fn (Get $get): bool => in_array($get('widget_type'), $types, true);

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        Radio::make('widget_type')->label(__('announcement::announcement.fields.type'))->options(self::options('types', Widget::TYPES))
                            ->descriptions(self::options('type_help', Widget::TYPES))->default('bar')->required()->live()
                            // Settings › Announcements turns each kind on; an editor sees when this one is still off
                            ->helperText(fn (Get $get): ?string => in_array($get('widget_type'), Announcements::disabled(), true)
                                ? __('announcement::announcement.settings.off', ['type' => __('announcement::announcement.types.'.$get('widget_type'))]) : null),
                        TranslatableTabs::make()->schema(fn (string $locale): array => [
                            TextInput::make("title.{$locale}")->label(__('announcement::announcement.fields.title'))->maxLength(150),
                            RichEditor::make("body.{$locale}")->label(__('announcement::announcement.fields.body'))->fileAttachments(false)->visible($is('popup')),
                            Textarea::make("body.{$locale}")->label(__('announcement::announcement.fields.text'))->rows(2)->maxLength(300)->visible($is('ticker', 'bar')),
                            TextInput::make("button_text.{$locale}")->label(__('announcement::announcement.fields.button_text'))->maxLength(60)->visible($is('popup', 'bar', 'countdown')),
                            TextInput::make("after_end_text.{$locale}")->label(__('announcement::announcement.fields.after_end_text'))->maxLength(150)
                                ->visible(fn (Get $get): bool => $get('widget_type') === 'countdown' && $get('after_end') === 'message'),
                        ]),
                        TextInput::make('link_url')->label(__('announcement::announcement.fields.link_url'))->helperText(__('announcement::announcement.help.link_url'))
                            ->regex('#^(https?://\S+|/\S*)$#i')->maxLength(2048),
                    ]),
                    Section::make(__('announcement::announcement.sections.details'))->compact()->schema([
                        // Popup
                        MediaPicker::make('image')->label(__('announcement::announcement.fields.image'))->image()->visible($is('popup')),
                        Grid::make(2)->visible($is('popup'))->schema([
                            Select::make('frequency')->label(__('announcement::announcement.fields.frequency'))->options(self::options('frequencies', Widget::FREQUENCIES))->default('daily')->selectablePlaceholder(false),
                            TextInput::make('priority')->label(__('announcement::announcement.fields.priority'))->helperText(__('announcement::announcement.help.priority'))->integer()->default(0),
                        ]),
                        // Ticker
                        Toggle::make('include_latest_notices')->label(__('announcement::announcement.fields.include_latest_notices'))->helperText(__('announcement::announcement.help.include_latest_notices'))
                            ->visible($is('ticker')),
                        // Countdown
                        Grid::make(2)->visible($is('countdown'))->schema([
                            DateTimePicker::make('target_at')->label(__('announcement::announcement.fields.target_at'))->seconds(false)->required($is('countdown')),
                            Select::make('after_end')->label(__('announcement::announcement.fields.after_end'))->options(self::options('after_end', ['hide', 'message']))->default('hide')->selectablePlaceholder(false)->live(),
                        ]),
                        // Bar and countdown
                        Grid::make(2)->visible($is('bar', 'countdown'))->schema([
                            Select::make('color_token')->label(__('announcement::announcement.fields.color_token'))->options(self::options('colors', Widget::COLORS))->placeholder(__('announcement::announcement.colors.color-primary')),
                            Toggle::make('is_dismissible')->label(__('announcement::announcement.fields.is_dismissible'))->default(true)->inline(false)->visible($is('bar')),
                        ]),
                    ]),
                    Section::make(__('announcement::announcement.sections.pages'))->compact()->schema([
                        Radio::make('pages')->label(__('announcement::announcement.fields.pages'))->options(self::options('pages', Widget::PAGES))->default('home')->inline()->live(),
                        Select::make('route_keys')->label(__('announcement::announcement.fields.route_keys'))->multiple()->searchable()
                            ->helperText(__('announcement::announcement.help.route_keys'))
                            ->options(fn (): array => app(CurrentPage::class)->options())
                            // Or an address of the site: "/admission" (the pages below it too)
                            ->createOptionForm([TextInput::make('path')->label(__('announcement::announcement.fields.path'))->regex('#^/\S*$#')->required()->maxLength(200)])
                            ->createOptionUsing(fn (array $data): string => '/'.trim((string) $data['path'], '/'))
                            ->getOptionLabelsUsing(fn (array $values): array => collect($values)->mapWithKeys(fn (string $v): array => [$v => app(CurrentPage::class)->options()[$v] ?? $v])->all())
                            ->visible(fn (Get $get): bool => $get('pages') === 'selected')->required(fn (Get $get): bool => $get('pages') === 'selected'),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('announcement::announcement.fields.title'))->searchable()->wrap()->limit(70)->placeholder('—')
                    ->description(fn (Widget $record): ?string => $record->widget_type === 'countdown' ? $record->target_at?->translatedFormat('d M Y, H:i') : null),
                TextColumn::make('widget_type')->label(__('announcement::announcement.fields.type'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("announcement::announcement.types.{$state}")),
                TextColumn::make('pages')->label(__('announcement::announcement.fields.pages'))
                    ->formatStateUsing(fn (string $state): string => __("announcement::announcement.pages.{$state}")),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Widget $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2),
                TextColumn::make('expires_at')->label(__('platform::relations.expires_at'))->dateTime('d M Y')->placeholder('—')->toggleable()
                    ->color(fn (Widget $record): ?string => $record->isExpired() ? 'danger' : null),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('widget_type')->label(__('announcement::announcement.fields.type'))->options(self::options('types', Widget::TYPES)),
                ContentFilters::shownIn(),
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
            'index' => ListWidgets::route('/'),
            'create' => CreateWidget::route('/create'),
            'edit' => EditWidget::route('/{record}/edit'),
        ];
    }
}
