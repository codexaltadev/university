<?php

namespace Modules\Partners\Filament\Resources\Partners;

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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Partners\Filament\Resources\Partners\Pages\CreatePartner;
use Modules\Partners\Filament\Resources\Partners\Pages\EditPartner;
use Modules\Partners\Filament\Resources\Partners\Pages\ListPartners;
use Modules\Partners\Models\Partner;
use Platform\Filament\Components\HighlightPanel;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PublishingPanel;
use Platform\Filament\Components\RelatedToField;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Filament\Tables\ContentFilters;
use UnitEnum;

/**
 * Services › Partners (M-37, FR-PRT-01..04): type, name, description per language, logo, website;
 * a discount also has its value, terms, category and validity; right: active, Show in (a lab's or a
 * department's partners), highlight. Dragged order.
 */
class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('partners::partners.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('partners::partners.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function typeOptions(): array
    {
        return collect(Partner::TYPES)->mapWithKeys(fn (string $t): array => [$t => __('partners::partners.types.'.$t)])->all();
    }

    public static function form(Schema $schema): Schema
    {
        $discount = fn (Get $get): bool => $get('partner_type') === 'discount';

        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Grid::make(1)->columnSpan(2)->schema([
                    Section::make()->schema([
                        Grid::make(2)->schema([
                            Select::make('partner_type')->label(__('partners::partners.fields.partner_type'))->options(self::typeOptions())->default('industry')->required()->live()->native(false),
                            TextInput::make('website_url')->label(__('partners::partners.fields.website_url'))->url()->maxLength(2048),
                        ]),
                        TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                            TextInput::make("name.{$locale}")->label(__('partners::partners.fields.name'))->required($isDefault)->maxLength(200),
                            Textarea::make("description.{$locale}")->label(__('partners::partners.fields.description'))->rows(3)->maxLength(2000),
                            TextInput::make("discount_value.{$locale}")->label(__('partners::partners.fields.discount_value'))->placeholder('20% off')->maxLength(100)->visible($discount),
                            Textarea::make("discount_terms.{$locale}")->label(__('partners::partners.fields.discount_terms'))->rows(2)->maxLength(1000)->visible($discount),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('valid_from')->label(__('partners::partners.fields.valid_from')),
                            DatePicker::make('valid_until')->label(__('partners::partners.fields.valid_until'))->afterOrEqual('valid_from')->helperText(__('partners::partners.help.valid_until')),
                            Toggle::make('is_international')->label(__('partners::partners.fields.is_international'))->inline(false),
                        ]),
                        MediaPicker::make('logo')->label(__('partners::partners.fields.logo'))->image(),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->visible($discount)->schema([TermSelect::make('category')->vocabulary(Partner::CATEGORY)]),
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
                ImageColumn::make('logo')->label('')->imageSize(36)->state(fn (Partner $record): ?string => $record->firstMedia('logo')?->url('thumb')),
                TextColumn::make('name')->label(__('partners::partners.fields.name'))->searchable()->wrap(),
                TextColumn::make('partner_type')->label(__('partners::partners.fields.partner_type'))->badge()->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),
                TextColumn::make('valid_until')->label(__('partners::partners.fields.valid_until'))->date()->toggleable()
                    ->color(fn (Partner $record): ?string => $record->valid_until?->isPast() ? 'danger' : null),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Partner $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('partner_type')->label(__('partners::partners.fields.partner_type'))->options(self::typeOptions()),
                ContentFilters::shownIn(), ContentFilters::term('category', Partner::CATEGORY), ContentFilters::mainSite(), TrashedFilter::make(),
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
            'index' => ListPartners::route('/'),
            'create' => CreatePartner::route('/create'),
            'edit' => EditPartner::route('/{record}/edit'),
        ];
    }
}
