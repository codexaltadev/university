<?php

namespace Modules\Transport\Filament\Resources\Routes;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Transport\Filament\Resources\Routes\Pages\CreateRoute;
use Modules\Transport\Filament\Resources\Routes\Pages\EditRoute;
use Modules\Transport\Filament\Resources\Routes\Pages\ListRoutes;
use Modules\Transport\Filament\Resources\Routes\RelationManagers\VariantsRelationManager;
use Modules\Transport\Models\Route;
use Modules\Transport\Models\ServiceType;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/**
 * Transport › Routes (DB-06 v1.2 §2, FR-TRN-02/04/09): name and message per language, group on the
 * website, code, map; below, its stops and times per service type (variants). Dragged order.
 */
class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static string|UnitEnum|null $navigationGroup = 'Transport';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('transport::transport.route');
    }

    public static function getPluralModelLabel(): string
    {
        return __('transport::transport.routes');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function groupOptions(): array
    {
        return collect(Route::GROUPS)->mapWithKeys(fn (string $g): array => [$g => __("transport::transport.groups.{$g}_road")])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                TranslatableTabs::make()->schema(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("name.{$locale}")->label(__('transport::transport.fields.name'))->required($isDefault)->maxLength(200),
                    Textarea::make("message.{$locale}")->label(__('transport::transport.fields.message'))->helperText(__('transport::transport.help.message'))->rows(3)->maxLength(2000),
                ]),
                Grid::make(3)->schema([
                    Select::make('route_group')->label(__('transport::transport.fields.route_group'))->options(self::groupOptions())->default('regular')->selectablePlaceholder(false)->required(),
                    TextInput::make('code')->label(__('transport::transport.fields.code'))->maxLength(30),
                    Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
                ]),
                TextInput::make('map_url')->label(__('transport::transport.fields.map_url'))->helperText(__('transport::transport.help.map_url'))
                    ->regex('#^https://\S+$#i')->maxLength(2048),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('variants.serviceType'))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('transport::transport.fields.name'))->searchable()->wrap(),
                TextColumn::make('route_group')->label(__('transport::transport.fields.route_group'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("transport::transport.groups.{$state}_road")),
                TextColumn::make('services')->label(__('transport::transport.service_types'))->badge()->color('gray')
                    ->state(fn (Route $record): array => $record->variants->map(fn ($v) => (string) $v->serviceType?->name)->filter()->all()),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([
                SelectFilter::make('route_group')->label(__('transport::transport.fields.route_group'))->options(self::groupOptions()),
                SelectFilter::make('service')->label(__('transport::transport.service_types'))
                    ->options(fn (): array => ServiceType::query()->ordered()->get()->mapWithKeys(fn (ServiceType $t): array => [$t->id => (string) $t->name])->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->whereHas('variants', fn (Builder $v) => $v->where('service_type_id', $data['value'])) : $query),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make()]);
    }

    public static function getRelations(): array
    {
        return [VariantsRelationManager::class, ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoutes::route('/'),
            'create' => CreateRoute::route('/create'),
            'edit' => EditRoute::route('/{record}/edit'),
        ];
    }
}
