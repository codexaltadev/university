<?php

namespace Modules\Lab\Filament\Resources\Labs;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Lab\Filament\Resources\Labs\Pages\CreateLab;
use Modules\Lab\Filament\Resources\Labs\Pages\EditLab;
use Modules\Lab\Filament\Resources\Labs\Pages\ListLabs;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\EquipmentRelationManager;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\ProductsRelationManager;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\ProjectsRelationManager;
use Modules\Lab\Filament\Resources\Labs\RelationManagers\ScopesRelationManager;
use Modules\Lab\Models\Lab;
use Platform\Filament\Components\MediaPicker;
use Platform\Filament\Components\PeopleManager;
use Platform\Filament\Components\SeoPanel;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\Components\WebAddressField;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use Platform\Taxonomy\Vocabularies;
use UnitEnum;

/**
 * Services › Labs (M-32, FR-LAB-01..04): name, summary, details, equipment summary, location per
 * language; type, Department, contact, video, website, logo and cover from the Media Library; people
 * (in-charge, team) by the Person Picker; equipment, scopes, projects and products below.
 */
class LabResource extends Resource
{
    protected static ?string $model = Lab::class;

    protected static string|UnitEnum|null $navigationGroup = 'Services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('lab::lab.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('lab::lab.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<int, string> */
    public static function departmentOptions(): array
    {
        return class_exists(Lab::DEPARTMENT)
            ? (Lab::DEPARTMENT)::query()->get()->sortBy(fn ($d): string => (string) $d->name)->mapWithKeys(fn ($d): array => [$d->getKey() => trim(($d->short_name ? $d->short_name.' — ' : '').$d->name)])->all()
            : [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->columnSpanFull()->schema([
                Section::make()->columnSpan(2)->schema([
                    TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("name.{$locale}")->label(__('lab::lab.fields.name'))->required($isDefault)->maxLength(255),
                        Textarea::make("summary.{$locale}")->label(__('lab::lab.fields.summary'))->rows(2)->maxLength(600),
                        RichEditor::make("body.{$locale}")->label(__('lab::lab.fields.body'))->fileAttachments(false),
                        RichEditor::make("equipment_summary.{$locale}")->label(__('lab::lab.fields.equipment_summary'))->fileAttachments(false),
                        TextInput::make("location.{$locale}")->label(__('lab::lab.fields.location'))->maxLength(255),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('video_url')->label(__('lab::lab.fields.video_url'))->url()->maxLength(2048),
                        TextInput::make('website_url')->label(__('lab::lab.fields.website_url'))->url()->maxLength(2048),
                    ]),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Section::make(__('lab::lab.sections.lab'))->compact()->schema([
                        TermSelect::make('type')->vocabulary(Lab::TYPE),
                        Select::make('department_id')->label(__('lab::lab.fields.department'))->options(fn (): array => self::departmentOptions())->searchable()
                            ->visible(fn (): bool => class_exists(Lab::DEPARTMENT))->helperText(__('lab::lab.help.department')),
                        TextInput::make('short_name')->label(__('lab::lab.fields.short_name'))->maxLength(50),
                        WebAddressField::forMicrosite(Lab::class),
                        Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
                    ]),
                    Section::make(__('lab::lab.sections.contact'))->compact()->schema([
                        TextInput::make('email')->label(__('lab::lab.fields.email'))->email()->maxLength(191),
                        TextInput::make('phone')->label(__('lab::lab.fields.phone'))->tel()->maxLength(50),
                    ]),
                    Section::make(__('lab::lab.sections.pictures'))->compact()->schema([
                        MediaPicker::make('logo')->label(__('lab::lab.fields.logo'))->image(),
                        MediaPicker::make('cover')->label(__('lab::lab.fields.cover'))->image(),
                    ]),
                    SeoPanel::make(),
                ]),
            ]),
            PeopleManager::make()->columnSpanFull(),   // in-charge, team
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('terms')->withCount(['items as equipment_count' => fn ($q) => $q->where('item_type', 'equipment'), 'items as projects_count' => fn ($q) => $q->where('item_type', 'project')]))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')->label('')->imageSize(32)->state(fn (Lab $record): ?string => $record->firstMedia('logo')?->url('thumb')),
                TextColumn::make('name')->label(__('lab::lab.fields.name'))->searchable()->wrap()->description(fn (Lab $record): ?string => $record->short_name),
                TextColumn::make('type')->label(__('lab::lab.fields.type'))->badge()->color('gray')->state(fn (Lab $record): ?string => $record->termsIn(Lab::TYPE)->first()?->name),
                TextColumn::make('department_id')->label(__('lab::lab.fields.department'))->formatStateUsing(fn ($state): string => self::departmentOptions()[$state] ?? '')->toggleable()
                    ->visible(fn (): bool => class_exists(Lab::DEPARTMENT)),
                TextColumn::make('equipment_count')->label(__('lab::lab.types_plural.equipment'))->numeric()->toggleable(),
                TextColumn::make('projects_count')->label(__('lab::lab.types_plural.project'))->numeric()->toggleable(),
                ToggleColumn::make('is_active')->label(__('platform::relations.active'))->disabled(fn ($record): bool => ! auth()->user()?->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('type')->label(__('lab::lab.fields.type'))->options(fn (): array => app(Vocabularies::class)->options(Lab::TYPE))
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null) ? $q->withTerm((int) $data['value']) : $q),
                SelectFilter::make('department_id')->label(__('lab::lab.fields.department'))->options(fn (): array => self::departmentOptions())->searchable()
                    ->visible(fn (): bool => class_exists(Lab::DEPARTMENT)),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ScopesRelationManager::class, EquipmentRelationManager::class, ProjectsRelationManager::class, ProductsRelationManager::class, ActivityRelationManager::class];   // FR-LAB-04
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLabs::route('/'),
            'create' => CreateLab::route('/create'),
            'edit' => EditLab::route('/{record}/edit'),
        ];
    }
}
