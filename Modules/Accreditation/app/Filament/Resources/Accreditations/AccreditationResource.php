<?php

namespace Modules\Accreditation\Filament\Resources\Accreditations;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Accreditation\Filament\Resources\Accreditations\Pages\CreateAccreditation;
use Modules\Accreditation\Filament\Resources\Accreditations\Pages\EditAccreditation;
use Modules\Accreditation\Filament\Resources\Accreditations\Pages\ListAccreditations;
use Modules\Accreditation\Models\Accreditation;
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
 * Services › Accreditations (M-34, FR-ACR-01/02): title, agency, text per language; awarded /
 * expires; logo and certificate from the Media Library; right: active, Show in (a program,
 * department, faculty), highlight, category, address, SEO. Dragged order.
 */
class AccreditationResource extends Resource
{
    protected static ?string $model = Accreditation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('accreditation::accreditation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('accreditation::accreditation.plural');
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
                            TextInput::make("title.{$locale}")->label(__('accreditation::accreditation.fields.title'))->required($isDefault)->maxLength(250),
                            TextInput::make("agency.{$locale}")->label(__('accreditation::accreditation.fields.agency'))->maxLength(200),
                            RichEditor::make("body.{$locale}")->label(__('accreditation::accreditation.fields.body'))->fileAttachments(false),
                        ]),
                        Grid::make(3)->schema([
                            DatePicker::make('awarded_on')->label(__('accreditation::accreditation.fields.awarded_on')),
                            DatePicker::make('expires_on')->label(__('accreditation::accreditation.fields.expires_on'))->afterOrEqual('awarded_on')->helperText(__('accreditation::accreditation.help.expires_on')),
                            TextInput::make('website_url')->label(__('accreditation::accreditation.fields.website_url'))->url()->maxLength(2048),
                        ]),
                    ]),
                    Section::make(__('accreditation::accreditation.sections.media'))->compact()->collapsible()->schema([
                        MediaPicker::make('logo')->label(__('accreditation::accreditation.fields.logo'))->image(),
                        MediaPicker::make('certificate')->label(__('accreditation::accreditation.fields.certificate')),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    PublishingPanel::make(),
                    Section::make()->compact()->schema([WebAddressField::make(prefix: '/'.Accreditation::PATH.'/')]),
                    RelatedToField::make(),
                    HighlightPanel::make(),
                    Section::make()->compact()->schema([TermSelect::make('category')->vocabulary(Accreditation::CATEGORY)]),
                    SeoPanel::make(),
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
                ImageColumn::make('logo')->label('')->imageSize(36)->state(fn (Accreditation $record): ?string => $record->firstMedia('logo')?->url('thumb')),
                TextColumn::make('title')->label(__('accreditation::accreditation.fields.title'))->searchable()->wrap()->description(fn (Accreditation $record): ?string => $record->agency),
                TextColumn::make('expires_on')->label(__('accreditation::accreditation.fields.expires_on'))->date()->sortable()
                    ->color(fn (Accreditation $record): ?string => $record->isExpired() ? 'danger' : null),
                TextColumn::make('shown_in')->label(__('platform::relations.show_in'))
                    ->state(fn (Accreditation $record): array => ContentFilters::placesOf($record))->badge()->color('gray')->limitList(2)->toggleable(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([ContentFilters::shownIn(), ContentFilters::term('category', Accreditation::CATEGORY), ContentFilters::mainSite(), TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccreditations::route('/'),
            'create' => CreateAccreditation::route('/create'),
            'edit' => EditAccreditation::route('/{record}/edit'),
        ];
    }
}
