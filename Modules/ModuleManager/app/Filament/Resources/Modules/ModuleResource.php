<?php

namespace Modules\ModuleManager\Filament\Resources\Modules;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\ModuleManager\Filament\Resources\Modules\Pages\ListModules;
use Modules\ModuleManager\Filament\Resources\Modules\Pages\ViewModule;
use Modules\ModuleManager\Filament\Resources\Modules\Schemas\ModuleInfolist;
use Modules\ModuleManager\Filament\Resources\Modules\Tables\ModulesTable;
use Platform\Models\CoreModule;

/** System › Modules (C-01, UI/UX Part A §7.9). */
class ModuleResource extends Resource
{
    protected static ?string $model = CoreModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'modules';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function getModelLabel(): string
    {
        return __('modulemanager::manager.module');
    }

    public static function infolist(Schema $schema): Schema
    {
        return ModuleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ModulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'view' => ViewModule::route('/{record}'),
        ];
    }
}
