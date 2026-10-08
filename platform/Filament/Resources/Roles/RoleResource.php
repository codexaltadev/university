<?php

namespace Platform\Filament\Resources\Roles;

use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Platform\Filament\Resources\Roles\Pages\EditRole;
use Platform\Modules\ModulePermissions;
use Platform\Permissions\EntityPermissions;

/**
 * Shield's role editor plus two platform tabs (SAD Part A §7.4):
 * - Entity permissions: dynamic Office / Lab permissions (POC-3)
 * - Inactive modules: kept but read-only, so saving a role never drops them (POC-4)
 */
class RoleResource extends ShieldRoleResource
{
    public static function getShieldFormComponents(): Component
    {
        return Tabs::make('Permissions')
            ->contained()
            ->tabs([
                static::getTabFormComponentForResources(),
                static::getTabFormComponentForPage(),
                static::getTabFormComponentForWidget(),
                static::getTabFormComponentForCustomPermissions(),
                static::getTabFormComponentForEntityPermissions(),
                static::getTabFormComponentForInactiveModules(),
            ])
            ->columnSpan('full');
    }

    public static function getTabFormComponentForEntityPermissions(): Component
    {
        $groups = app(EntityPermissions::class)->options();
        $count = collect($groups)->sum(fn (array $options): int => count($options));

        return Tab::make('entity_permissions')
            ->label(__('Entity permissions'))
            ->visible($count > 0)
            ->badge($count)
            ->schema(collect($groups)
                ->map(fn (array $options, string $subject): Section => Section::make(Str::plural($subject))
                    ->compact()
                    ->collapsible()
                    ->schema([
                        static::getCheckboxListFormComponent(
                            name: 'entity_permissions_'.Str::snake($subject),
                            options: $options,
                        ),
                    ]))
                ->values()
                ->all());
    }

    public static function getTabFormComponentForInactiveModules(): Component
    {
        $modules = app(ModulePermissions::class)->inactiveModulePermissions();
        $count = collect($modules)->sum(fn (array $keys): int => count($keys));

        return Tab::make('inactive_modules')
            ->label(__('Inactive modules'))
            ->visible($count > 0)
            ->badge($count)
            ->badgeColor('gray')
            ->schema(collect($modules)
                ->map(fn (array $keys, string $module): Section => Section::make($module)
                    ->description(__('Module is inactive. Permissions are kept and apply again when the module is activated.'))
                    ->compact()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        CheckboxList::make('inactive_module_'.Str::snake($module))
                            ->hiddenLabel()
                            ->options(array_combine($keys, $keys))
                            ->disabled()
                            ->dehydrated(false)
                            ->columns(static::shield()->getCheckboxListColumns())
                            ->afterStateHydrated(function (CheckboxList $component, ?Model $record) use ($keys): void {
                                $component->state($record
                                    ? array_values(array_filter($keys, fn (string $key): bool => $record->hasPermissionTo($key)))
                                    : []);
                            }),
                    ]))
                ->values()
                ->all());
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
