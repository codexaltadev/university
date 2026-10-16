<?php

namespace Modules\Links\Filament\Resources\Links;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Modules\Links\Filament\Resources\Links\Pages\CreateLink;
use Modules\Links\Filament\Resources\Links\Pages\EditLink;
use Modules\Links\Filament\Resources\Links\Pages\ListLinks;
use Modules\Links\Models\Link;
use Modules\Links\Support\LinkChecker;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Content › Important Links (M-29, FR-LINK-01..03): title and description per language, address,
 * new tab, icon from the Media Library, group; right: active, Show in. Dragged order; status of
 * the last check, "Broken" filter, "Check now".
 */
class LinkResource extends Resource
{
    protected static ?string $model = Link::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?int $navigationSort = 19;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('links::links.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('links::links.plural');
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
                            TextInput::make("title.{$locale}")->label(__('links::links.fields.title'))->required($isDefault)->maxLength(150),
                            Textarea::make("description.{$locale}")->label(__('links::links.fields.description'))->rows(2)->maxLength(300),
                        ]),
                        TextInput::make('url')->label(__('links::links.fields.url'))->helperText(__('links::links.help.url'))
                            ->required()->maxLength(2048)->regex('#^(https?://\S+|/\S*|mailto:\S+|tel:\S+)$#i'),
                        Grid::make(2)->schema([
                            Toggle::make('open_in_new_tab')->label(__('links::links.fields.open_in_new_tab'))->default(true)->inline(false),
                            TermSelect::make('group')->vocabulary(Link::GROUP),
                        ]),
                        MediaPicker::make('icon')->label(__('links::links.fields.icon'))->helperText(__('links::links.help.icon'))->image(),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('icon')->label('')->imageSize(32)->state(fn (Link $record): ?string => $record->firstMedia('icon')?->url('thumb')),
                TextColumn::make('title')->label(__('links::links.fields.title'))->searchable()->wrap()
                    ->description(fn (Link $record): string => $record->url),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Link $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                TextColumn::make('last_status_code')->label(__('links::links.fields.status'))->badge()
                    ->state(fn (Link $record): string => match (true) {
                        ! $record->isExternal() => __('links::links.status.internal'),
                        $record->last_checked_at === null => __('links::links.status.unchecked'),
                        $record->isBroken() => __('links::links.status.broken', ['code' => $record->last_status_code ?: '—']),
                        default => __('links::links.status.ok'),
                    })
                    ->color(fn (Link $record): string => $record->isBroken() ? 'danger' : ($record->last_checked_at ? 'success' : 'gray'))
                    ->tooltip(fn (Link $record): ?string => $record->last_checked_at?->diffForHumans()),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                Filter::make('broken')->label(__('links::links.status.broken_filter'))->query(fn (Builder $query) => $query->broken()),
                ContentFilters::shownIn(),
                ContentFilters::term('group', Link::GROUP),
                ContentFilters::mainSite(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('check')->label(__('links::links.check_now'))->icon(Heroicon::OutlinedSignal)->color('gray')
                    ->visible(fn (Link $record): bool => $record->isExternal())
                    ->action(function (Link $record): void {
                        $code = app(LinkChecker::class)->status($record->url);
                        DB::table($record->getTable())->where('id', $record->id)->update(['last_checked_at' => now(), 'last_status_code' => $code]);
                        $ok = $code > 0 && $code < 400;
                        Notification::make()->{$ok ? 'success' : 'danger'}()->title($ok ? __('links::links.status.ok') : __('links::links.status.broken', ['code' => $code ?: '—']))->send();
                    }),
                EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLinks::route('/'),
            'create' => CreateLink::route('/create'),
            'edit' => EditLink::route('/{record}/edit'),
        ];
    }
}
