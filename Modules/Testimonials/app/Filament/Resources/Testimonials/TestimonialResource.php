<?php

namespace Modules\Testimonials\Filament\Resources\Testimonials;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
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
use Modules\Testimonials\Filament\Resources\Testimonials\Pages\CreateTestimonial;
use Modules\Testimonials\Filament\Resources\Testimonials\Pages\EditTestimonial;
use Modules\Testimonials\Filament\Resources\Testimonials\Pages\ListTestimonials;
use Modules\Testimonials\Models\Testimonial;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PersonPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use Platform\Support\Countries;
use UnitEnum;

/**
 * Content › Testimonials (M-21, FR-TEST-01..04): the person (from a directory or added here) and
 * their title, the quote per language, type, rating, video; a photo when the person has none;
 * right: active, Show in, highlight. Order is dragged in the list.
 */
class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static ?int $navigationSort = 14;

    public static function getModelLabel(): string
    {
        return __('testimonials::testimonials.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('testimonials::testimonials.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(Testimonial::TYPES)->mapWithKeys(fn (string $t): array => [$t => __("testimonials::testimonials.types.{$t}")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make(__('testimonials::testimonials.sections.person'))->schema([
                        PersonPicker::make('person')->label(__('testimonials::testimonials.fields.person'))
                            ->helperText(__('testimonials::testimonials.help.person'))->required(),
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("author_title.{$locale}")->label(__('testimonials::testimonials.fields.author_title'))
                                ->helperText(__('testimonials::testimonials.help.author_title'))->maxLength(150),
                            Textarea::make("quote.{$locale}")->label(__('testimonials::testimonials.fields.quote'))->required($isDefault)->rows(5)->maxLength(2000),
                        ]),
                    ]),
                    Section::make()->columns(2)->schema([
                        Select::make('testimonial_type')->label(__('testimonials::testimonials.fields.type'))->options(self::typeOptions())->default('student')->required(),
                        Select::make('country')->label(__('testimonials::testimonials.fields.country'))->helperText(__('testimonials::testimonials.help.country'))
                            ->options(fn (): array => Countries::options())->searchable(),
                        Select::make('rating')->label(__('testimonials::testimonials.fields.rating'))->options(array_combine(range(1, 5), array_map(fn (int $n): string => str_repeat('★', $n), range(1, 5)))),
                        TextInput::make('video_url')->label(__('testimonials::testimonials.fields.video_url'))->helperText(__('testimonials::testimonials.help.video_url'))
                            ->url()->maxLength(2048)->columnSpanFull(),
                        MediaPicker::make('photo')->label(__('testimonials::testimonials.fields.photo'))->helperText(__('testimonials::testimonials.help.photo'))->image()->columnSpanFull(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    HighlightPanel::make(),
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
                ImageColumn::make('photo')->label('')->circular()->imageSize(36)
                    ->state(fn (Testimonial $record): ?string => $record->photoUrl()),
                TextColumn::make('person')->label(__('testimonials::testimonials.fields.person'))
                    ->state(fn (Testimonial $record): ?string => $record->person()?->name() ?? $record->author()?->person?->personPresenter()->name())
                    ->description(fn (Testimonial $record): ?string => $record->person()?->roleLabel ?: null),
                TextColumn::make('quote')->label(__('testimonials::testimonials.fields.quote'))->searchable()->wrap()->limit(90),
                TextColumn::make('testimonial_type')->label(__('testimonials::testimonials.fields.type'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("testimonials::testimonials.types.{$state}")),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Testimonial $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                TextColumn::make('rating')->label(__('testimonials::testimonials.fields.rating'))
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '')->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('testimonial_type')->label(__('testimonials::testimonials.fields.type'))->options(self::typeOptions()),
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
            'index' => ListTestimonials::route('/'),
            'create' => CreateTestimonial::route('/create'),
            'edit' => EditTestimonial::route('/{record}/edit'),
        ];
    }
}
