<?php

namespace Modules\User\Filament\Resources\Users\Schemas;

use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Modules\User\Services\UserAccess;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('user::user.form.account'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label(__('user::user.fields.name'))->required()->maxLength(191),
                    TextInput::make('email')->label(__('user::user.fields.email'))->email()->required()->maxLength(191)
                        ->unique(ignoreRecord: true),
                    TextInput::make('username')->label(__('user::user.fields.username'))->maxLength(60)
                        ->alphaDash()->unique(ignoreRecord: true),
                    TextInput::make('phone')->label(__('user::user.fields.phone'))->tel()->maxLength(30),
                    Select::make('locale')->label(__('user::user.fields.locale'))
                        ->options(fn (): array => collect(config('platform.locales'))->mapWithKeys(fn (string $l): array => [$l => __("user::user.locales.{$l}")])->all())
                        ->default(config('app.locale'))
                        ->required(),
                    Toggle::make('is_active')->label(__('user::user.fields.active'))->default(true)
                        ->disabled(fn (?User $record): bool => $record?->is(Auth::user()) ?? false)   // no self-lockout
                        ->helperText(__('user::user.help.active')),
                ]),

            Section::make(__('user::user.form.access'))
                ->description(__('user::user.help.access'))
                ->schema([
                    Select::make('roles')->label(__('user::user.fields.roles'))
                        ->relationship('roles', 'name', fn (Builder $query) => Auth::user()?->isSuperAdmin()
                            ? $query
                            : $query->where('name', '!=', config('filament-shield.super_admin.name')))
                        ->getOptionLabelFromRecordUsing(fn (Role $role): string => Str::headline($role->name))
                        ->multiple()
                        ->preload()
                        ->rule(static fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                            $superAdmin = Role::query()->where('name', config('filament-shield.super_admin.name'))->value('id');
                            if (in_array($superAdmin, (array) $value) && ! Auth::user()?->isSuperAdmin()) {
                                $fail(__('user::user.errors.super_admin_role'));
                            }
                        }),
                    Select::make('scope_keys')->label(__('user::user.fields.scope'))
                        ->helperText(__('user::user.help.scope'))
                        ->options(fn (): array => app(UserAccess::class)->scopeOptions())
                        ->multiple()
                        ->searchable()
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Select $component, ?User $record): void {
                            $component->state($record ? app(UserAccess::class)->scopeKeys($record) : []);
                        }),
                ]),

            Section::make(__('user::user.form.password'))
                ->columns(2)
                ->schema([
                    Toggle::make('send_invitation')->label(__('user::user.fields.send_invitation'))
                        ->helperText(__('user::user.help.send_invitation'))
                        ->default(true)
                        ->live()
                        ->dehydrated(false)
                        ->visibleOn('create'),
                    TextInput::make('password')->label(fn (string $operation): string => $operation === 'create' ? __('user::user.fields.password') : __('user::user.fields.new_password'))
                        ->password()
                        ->revealable()
                        ->rule(Password::min(8)->mixedCase()->numbers())   // NFR-SEC-02
                        ->required(fn (string $operation, Get $get): bool => $operation === 'create' && ! $get('send_invitation'))
                        ->visible(fn (string $operation, Get $get): bool => $operation === 'edit' || ! $get('send_invitation'))
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ]),
        ]);
    }
}
