<?php

namespace Modules\Admission\Filament\Resources\Subscribers;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\Subscribers\Pages\ManageAdmissionSubscribers;
use Modules\Admission\Models\AdmissionSubscriber;

/** Admission › Subscribers (FR-ADM-09/12): who asked for admission news; filter, unsubscribe, export. */
class AdmissionSubscriberResource extends Resource
{
    protected static ?string $model = AdmissionSubscriber::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'subscribers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 6;

    public static function getModelLabel(): string
    {
        return __('admission::admission.subscriber.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.subscriber.plural');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('contact')->label(__('admission::admission.fields.contact'))->searchable()->copyable(),
                TextColumn::make('channel')->label(__('admission::admission.fields.channel'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("admission::admission.channels.{$state}")),
                TextColumn::make('topic')->label(__('admission::admission.fields.topic'))->badge()->color('gray'),
                TextColumn::make('source')->label(__('admission::admission.fields.source'))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label(__('admission::admission.fields.subscribed_at'))->date('d M Y')->sortable(),
                TextColumn::make('unsubscribed_at')->label(__('admission::admission.fields.unsubscribed_at'))->date('d M Y')->placeholder('—')
                    ->description(fn (AdmissionSubscriber $record): ?string => $record->unsubscribe_reason),
            ])
            ->filters([
                SelectFilter::make('channel')->label(__('admission::admission.fields.channel'))
                    ->options(collect(AdmissionSubscriber::CHANNELS)->mapWithKeys(fn (string $c): array => [$c => __("admission::admission.channels.{$c}")])->all()),
                SelectFilter::make('topic')->label(__('admission::admission.fields.topic'))
                    ->options(fn (): array => AdmissionSubscriber::query()->distinct()->orderBy('topic')->pluck('topic', 'topic')->all()),
                TernaryFilter::make('subscribed')->label(__('admission::admission.fields.subscribed'))
                    ->queries(true: fn ($q) => $q->whereNull('unsubscribed_at'), false: fn ($q) => $q->whereNotNull('unsubscribed_at')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('unsubscribe')->label(__('admission::admission.unsubscribe'))->icon(Heroicon::OutlinedNoSymbol)->color('gray')
                    ->requiresConfirmation()
                    ->authorize(fn (AdmissionSubscriber $record): bool => (bool) auth()->user()?->can('update', $record))
                    ->visible(fn (AdmissionSubscriber $record): bool => $record->unsubscribed_at === null)
                    ->action(fn (AdmissionSubscriber $record) => $record->update(['unsubscribed_at' => now(), 'unsubscribe_reason' => __('admission::admission.by_admin')])),
                DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdmissionSubscribers::route('/')];
    }
}
