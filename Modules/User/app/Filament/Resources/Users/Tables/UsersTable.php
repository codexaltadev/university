<?php

namespace Modules\User\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\User\Services\UserAccess;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles')->withCount('scopes'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label(__('user::user.fields.name'))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email', 'username'])
                    ->sortable(),
                TextColumn::make('roles.name')->label(__('user::user.fields.roles'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Str::headline($state)),
                TextColumn::make('scopes_count')->label(__('user::user.fields.scope'))
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? __('user::user.scope.all') : trans_choice('user::user.scope.count', $state))
                    ->badge()
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'info'),
                ToggleColumn::make('is_active')->label(__('user::user.fields.active'))
                    ->disabled(fn (User $record): bool => $record->is(Auth::user()) || ! Auth::user()?->can('update', $record)),
                TextColumn::make('last_login_at')->label(__('user::user.fields.last_login'))
                    ->since()
                    ->placeholder(__('user::user.never'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label(__('user::user.fields.roles'))->relationship('roles', 'name')->multiple()->preload(),
                TernaryFilter::make('is_active')->label(__('user::user.fields.active')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('effectivePermissions')
                    ->label(__('user::user.actions.effective'))
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->modalHeading(fn (User $record): string => __('user::user.actions.effective').': '.$record->name)
                    ->modalContent(fn (User $record) => view('user::effective-permissions', app(UserAccess::class)->effective($record)))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('user::user.actions.close')),
                Action::make('resendInvitation')
                    ->label(__('user::user.actions.resend'))
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->visible(fn (User $record): bool => $record->last_login_at === null && ! $record->trashed() && (Auth::user()?->can('update', $record) ?? false))
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        app(UserAccess::class)->invite($record);
                        Notification::make()->title(__('user::user.invitation.sent', ['email' => $record->email]))->success()->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
