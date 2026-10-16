<?php

namespace Modules\Committee\Filament\Resources\Committees;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Modules\Committee\Filament\Resources\Committees\Pages\CreateCommittee;
use Modules\Committee\Filament\Resources\Committees\Pages\EditCommittee;
use Modules\Committee\Filament\Resources\Committees\Pages\ListCommittees;
use Modules\Committee\Models\Committee;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/**
 * People › Committees (M-16, FR-COM-01..07): name and description per language, category, term
 * dates, the order PDF and the members (person, committee role, own title, note) on the left;
 * active, Show in (entities, main site) and address on the right. "Copy to new term" starts
 * the next term with today's members.
 */
class CommitteeResource extends Resource
{
    protected static ?string $model = Committee::class;

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 23;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('committee::committee.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('committee::committee.plural');
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
                            TextInput::make("name.{$locale}")->label(__('committee::committee.fields.name'))->required($isDefault)->maxLength(191),
                            RichEditor::make("description.{$locale}")->label(__('committee::committee.fields.description'))->fileAttachments(false),
                        ]),
                        Grid::make(4)->schema([
                            Select::make('category_term_id')->label(__('committee::committee.fields.category'))
                                ->options(fn (): array => app(Vocabularies::class)->options(Committee::CATEGORY)),
                            DatePicker::make('formed_on')->label(__('committee::committee.fields.formed_on')),
                            DatePicker::make('term_starts_on')->label(__('committee::committee.fields.term_starts_on')),
                            DatePicker::make('term_ends_on')->label(__('committee::committee.fields.term_ends_on'))->afterOrEqual('term_starts_on')
                                ->helperText(__('committee::committee.help.term_ends_on')),
                        ]),
                    ]),
                    PeopleManager::make(withNote: true)->collapsed(false),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    Section::make()->compact()->schema([
                        WebAddressField::make(prefix: '/'.Committee::PATH.'/'),
                        MediaPicker::make('order_pdf')->label(__('committee::committee.fields.order_pdf'))
                            ->helperText(__('committee::committee.help.order_pdf')),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount(['assignments as members_count' => fn ($q) => $q->current()]))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('committee::committee.fields.name'))->searchable()->wrap()
                    ->description(fn (Committee $record): ?string => $record->isPrevious() ? __('committee::committee.previous_term') : null),
                TextColumn::make('category.name')->label(__('committee::committee.fields.category'))->badge(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Committee $record): array => ContentFilters::placesOf($record))->badge()->toggleable(),
                TextColumn::make('term')->label(__('committee::committee.term'))
                    ->state(fn (Committee $record): ?string => $record->termLabel()),
                TextColumn::make('members_count')->label(__('committee::committee.members'))->numeric(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))
                    ->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                ContentFilters::shownIn(),
                ContentFilters::mainSite(),
                SelectFilter::make('category_term_id')->label(__('committee::committee.fields.category'))
                    ->options(fn (): array => app(Vocabularies::class)->options(Committee::CATEGORY)),
                TernaryFilter::make('previous')->label(__('committee::committee.previous_term'))
                    ->queries(true: fn (Builder $q) => $q->where('term_ends_on', '<', Carbon::today()->toDateString()), false: fn (Builder $q) => $q->running(), blank: fn (Builder $q) => $q),
                TrashedFilter::make(),
            ])
            ->recordActions([static::copyAction(), EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    /** FR-COM-05: the next term with today's members; this one becomes a previous term. */
    public static function copyAction(): Action
    {
        return Action::make('newTerm')->label(__('committee::committee.copy.action'))->icon(Heroicon::OutlinedDocumentDuplicate)->color('gray')
            ->visible(fn (Committee $record): bool => (bool) auth()->user()?->can('create', Committee::class) && auth()->user()->can('update', $record) && ! $record->nextTerm()->exists())
            ->modalDescription(__('committee::committee.copy.help'))
            ->schema([
                DatePicker::make('starts_on')->label(__('committee::committee.fields.term_starts_on'))->required()->default(fn (): string => Carbon::today()->toDateString()),
                DatePicker::make('ends_on')->label(__('committee::committee.fields.term_ends_on'))->afterOrEqual('starts_on'),
            ])
            ->action(function (Committee $record, array $data): void {
                $next = $record->copyToNewTerm(Carbon::parse($data['starts_on']), filled($data['ends_on'] ?? null) ? Carbon::parse($data['ends_on']) : null);
                Notification::make()->success()->title(__('committee::committee.copy.done'))->send();
                redirect(static::getUrl('edit', ['record' => $next]));
            });
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommittees::route('/'),
            'create' => CreateCommittee::route('/create'),
            'edit' => EditCommittee::route('/{record}/edit'),
        ];
    }
}
