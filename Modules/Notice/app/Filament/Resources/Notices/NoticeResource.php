<?php

namespace Modules\Notice\Filament\Resources\Notices;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
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
use Modules\Notice\Filament\Resources\Notices\Pages\CreateNotice;
use Modules\Notice\Filament\Resources\Notices\Pages\EditNotice;
use Modules\Notice\Filament\Resources\Notices\Pages\ListNotices;
use Modules\Notice\Models\Notice;
use Platform\Filament\Components\AttachmentList;
use Platform\Filament\Components\HighlightPanel;
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
 * Content › Notices (M-20, FR-NOT-01..07): title and text per language, reference number, files
 * with their own titles; right: publishing (active, show from), on the board until, pinned /
 * urgent, address, Show in, highlight, category, SEO.
 */
class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('notice::notice.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notice::notice.plural');
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
                            TextInput::make("title.{$locale}")->label(__('notice::notice.fields.title'))->required($isDefault)->maxLength(255),
                            RichEditor::make("body.{$locale}")->label(__('notice::notice.fields.body'))->fileAttachments(false),
                        ]),
                        TextInput::make('reference_no')->label(__('notice::notice.fields.reference_no'))
                            ->helperText(__('notice::notice.help.reference_no'))->maxLength(100),
                    ]),
                    Section::make(__('notice::notice.fields.attachments'))->compact()->schema([AttachmentList::make('attachments')->hiddenLabel()]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([
                        DateTimePicker::make('expires_at')->label(__('notice::notice.fields.expires_at'))
                            ->helperText(__('notice::notice.help.expires_at'))->seconds(false),
                        Toggle::make('is_pinned')->label(__('notice::notice.fields.is_pinned'))->helperText(__('notice::notice.help.is_pinned')),
                        Toggle::make('is_urgent')->label(__('notice::notice.fields.is_urgent')),
                        WebAddressField::make(prefix: '/'.Notice::PATH.'/'),
                    ]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Notice::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms'])->withViewTotal())
            ->defaultSort('active_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('notice::notice.fields.title'))->searchable()->wrap()->limit(90)
                    ->description(fn (Notice $record): ?string => $record->reference_no),
                IconColumn::make('is_pinned')->label(__('notice::notice.fields.is_pinned'))->boolean(),
                IconColumn::make('is_urgent')->label(__('notice::notice.fields.is_urgent'))->boolean(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Notice $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                TextColumn::make('active_at')->label(__('platform::relations.active_at'))->dateTime('d M Y')->sortable()
                    ->description(fn (Notice $record): ?string => $record->isOnBoard() ? null : __('notice::notice.archived')),
                TextColumn::make('view_total')->label(__('notice::notice.views'))->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('board')->label(__('notice::notice.board'))
                    ->options(['on' => __('notice::notice.on_board'), 'archived' => __('notice::notice.archived')])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'on' => $query->onBoard(),
                        'archived' => $query->archived(),
                        default => $query,
                    }),
                TernaryFilter::make('is_pinned')->label(__('notice::notice.fields.is_pinned')),
                ContentFilters::shownIn(),
                ContentFilters::term('category', Notice::CATEGORY),
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
            'index' => ListNotices::route('/'),
            'create' => CreateNotice::route('/create'),
            'edit' => EditNotice::route('/{record}/edit'),
        ];
    }
}
