<?php

namespace Modules\Seo\Filament\Resources\NotFoundLogs;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Modules\Seo\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use Modules\Seo\Models\NotFoundLog;
use Modules\Seo\Models\Redirect;
use Modules\Seo\Support\Redirects;
use UnitEnum;

/** Site › 404 log (FR-SEO-06): missing addresses by count; one click to redirect one. */
class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 71;

    protected static ?string $recordTitleAttribute = 'path';

    public static function getModelLabel(): string
    {
        return __('seo::seo.not_found.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('seo::seo.not_found.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $open = NotFoundLog::query()->where('is_resolved', false)->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('path')->label(__('seo::seo.fields.path'))->searchable()->limit(70)->copyable(),
                TextColumn::make('hits')->label(__('seo::seo.fields.hits'))->numeric()->sortable(),
                TextColumn::make('last_seen_at')->label(__('seo::seo.fields.last_seen_at'))->since()->sortable(),
                TextColumn::make('first_seen_at')->label(__('seo::seo.fields.first_seen_at'))->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_referrer')->label(__('seo::seo.fields.last_referrer'))->limit(40)->toggleable(),
                IconColumn::make('is_resolved')->label(__('seo::seo.fields.is_resolved'))->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_resolved')->label(__('seo::seo.fields.is_resolved'))->default(false),
            ])
            ->recordActions([
                Action::make('redirect')->label(__('seo::seo.actions.create_redirect'))
                    ->icon(Heroicon::OutlinedArrowUturnRight)
                    ->visible(fn (NotFoundLog $record): bool => ! $record->is_resolved && (bool) auth()->user()?->can('create', Redirect::class))
                    ->modalDescription(fn (NotFoundLog $record): string => $record->path)
                    ->schema([
                        TextInput::make('destination_url')->label(__('seo::seo.fields.destination_url'))
                            ->placeholder('/new-page  ·  https://…')->required()->maxLength(2048),
                        Select::make('status_code')->label(__('seo::seo.fields.status_code'))
                            ->options([301 => __('seo::seo.status.301'), 302 => __('seo::seo.status.302')])->default(301)->selectablePlaceholder(false),
                    ])
                    ->action(function (NotFoundLog $record, array $data, Action $action): void {
                        $redirects = app(Redirects::class);
                        $values = ['source_path' => $record->path, 'destination_url' => $data['destination_url'], 'status_code' => $data['status_code'], 'source' => '404'];

                        if ($problem = $redirects->problem($values)) {
                            Notification::make()->danger()->title($problem)->send();
                            $action->halt();
                        }

                        $redirects->save($values);
                        Notification::make()->success()->title(__('seo::seo.notifications.redirect_created'))->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNotFoundLogs::route('/')];
    }
}
