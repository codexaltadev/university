<?php

namespace Modules\Achievements\Filament\Resources\Achievements;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Achievements\Filament\Resources\Achievements\Pages\CreateAchievement;
use Modules\Achievements\Filament\Resources\Achievements\Pages\EditAchievement;
use Modules\Achievements\Filament\Resources\Achievements\Pages\ListAchievements;
use Modules\Achievements\Models\Achievement;
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
 * Content › Achievements (M-24, FR-ACH-01..04): title, summary, text, competition, issuer,
 * position, team name per language; date and level; the achievers (Person Picker, a note each);
 * picture and gallery from the Media Library; related news; right: publishing, address, Show in,
 * highlight, category, SEO.
 */
class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?int $navigationSort = 17;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('achievements::achievements.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('achievements::achievements.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function levelOptions(): array
    {
        return collect(Achievement::LEVELS)->mapWithKeys(fn (string $l): array => [$l => __("achievements::achievements.levels.{$l}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $post = 'Modules\\News\\Models\\Post';

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("title.{$locale}")->label(__('achievements::achievements.fields.title'))->required($isDefault)->maxLength(255),
                            Textarea::make("summary.{$locale}")->label(__('achievements::achievements.fields.summary'))->rows(2)->maxLength(500),
                            Grid::make(2)->schema([
                                TextInput::make("competition.{$locale}")->label(__('achievements::achievements.fields.competition'))->maxLength(255),
                                TextInput::make("issuer.{$locale}")->label(__('achievements::achievements.fields.issuer'))->maxLength(255),
                                TextInput::make("position_label.{$locale}")->label(__('achievements::achievements.fields.position_label'))
                                    ->helperText($isDefault ? __('achievements::achievements.help.position_label') : null)->maxLength(100),
                                TextInput::make("team_name.{$locale}")->label(__('achievements::achievements.fields.team_name'))->maxLength(150),
                            ]),
                            RichEditor::make("body.{$locale}")->label(__('achievements::achievements.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('achieved_on')->label(__('achievements::achievements.fields.achieved_on'))->required()->default(now()),
                            Select::make('level')->label(__('achievements::achievements.fields.level'))->options(self::levelOptions())->default('national')->required(),
                            Select::make('related_post_id')->label(__('achievements::achievements.fields.related_post'))->searchable()
                                ->visible(fn (): bool => class_exists($post))
                                ->getSearchResultsUsing(fn (string $search): array => $post::query()->where('title', 'like', "%{$search}%")->latest('id')->limit(20)->get()
                                    ->mapWithKeys(fn ($p): array => [$p->id => (string) $p->title])->all())
                                ->getOptionLabelUsing(fn ($value): ?string => $post::query()->find($value)?->title),
                        ]),
                    ]),
                    PeopleManager::make(withNote: true)->heading(__('achievements::achievements.sections.achievers'))
                        ->description(__('achievements::achievements.help.achievers'))->collapsed(false),
                    Section::make(__('achievements::achievements.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('image')->label(__('achievements::achievements.fields.image'))->image(),
                        MediaPicker::make('gallery')->label(__('achievements::achievements.fields.gallery'))->image()->multiple(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Achievement::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Achievement::CATEGORY)]),
                    SeoPanel::make(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['contentRelations', 'terms']))
            ->defaultSort('achieved_on', 'desc')
            ->columns([
                ImageColumn::make('picture')->label('')->imageSize(40)->state(fn (Achievement $record): ?string => $record->firstMedia('image')?->url('thumb')),
                TextColumn::make('title')->label(__('achievements::achievements.fields.title'))->searchable()->wrap()->limit(80)
                    ->description(fn (Achievement $record): ?string => $record->position_label ?: null),
                TextColumn::make('level')->label(__('achievements::achievements.fields.level'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("achievements::achievements.levels.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'international' => 'success', 'national' => 'info', default => 'gray'
                    }),
                TextColumn::make('achieved_on')->label(__('achievements::achievements.fields.achieved_on'))->date('d M Y')->sortable(),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Achievement $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('level')->label(__('achievements::achievements.fields.level'))->options(self::levelOptions()),
                ContentFilters::shownIn(),
                ContentFilters::term('category', Achievement::CATEGORY),
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
            'index' => ListAchievements::route('/'),
            'create' => CreateAchievement::route('/create'),
            'edit' => EditAchievement::route('/{record}/edit'),
        ];
    }
}
