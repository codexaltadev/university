<?php

namespace Modules\Newsletter\Filament\Resources\Subscribers;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Newsletter\Filament\Resources\Subscribers\Pages\CreateSubscriber;
use Modules\Newsletter\Filament\Resources\Subscribers\Pages\EditSubscriber;
use Modules\Newsletter\Filament\Resources\Subscribers\Pages\ListSubscribers;
use Modules\Newsletter\Models\MailingList;
use Modules\Newsletter\Models\Subscriber;
use UnitEnum;

/** Communication › Subscribers (FR-NLT-04): email, name, status, lists; filters; import / export on the list. */
class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static ?string $slug = 'newsletter-subscribers';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 31;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getModelLabel(): string
    {
        return __('newsletter::newsletter.subscriber');
    }

    public static function getPluralModelLabel(): string
    {
        return __('newsletter::newsletter.subscribers');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(Subscriber::STATUSES)->mapWithKeys(fn (string $s): array => [$s => __('newsletter::newsletter.statuses.'.$s)])->all();
    }

    /** @return array<int, string> */
    public static function listOptions(): array
    {
        return MailingList::query()->ordered()->get()->mapWithKeys(fn (MailingList $l): array => [$l->id => (string) $l->name])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->schema([
                Grid::make(2)->schema([
                    TextInput::make('email')->label(__('newsletter::newsletter.fields.email'))->email()->required()->maxLength(191),
                    TextInput::make('name')->label(__('newsletter::newsletter.fields.name'))->maxLength(191),
                    Select::make('status')->label(__('newsletter::newsletter.fields.status'))->options(self::statusOptions())->default('subscribed')->required()->native(false)
                        ->helperText(__('newsletter::newsletter.help.status')),
                    Select::make('lists')->label(__('newsletter::newsletter.lists'))->relationship('lists', 'key')->multiple()->preload()
                        ->getOptionLabelFromRecordUsing(fn (MailingList $record): string => (string) $record->name),
                ]),
                TextInput::make('unsubscribe_reason')->label(__('newsletter::newsletter.fields.unsubscribe_reason'))->disabled()->visible(fn (?Subscriber $record): bool => filled($record?->unsubscribe_reason)),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('lists'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('email')->label(__('newsletter::newsletter.fields.email'))->searchable()->description(fn (Subscriber $record): ?string => $record->name),
                TextColumn::make('status')->label(__('newsletter::newsletter.fields.status'))->badge()
                    ->formatStateUsing(fn (string $state): string => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'subscribed' => 'success', 'pending' => 'warning', default => 'gray'
                    }),
                TextColumn::make('lists.key')->label(__('newsletter::newsletter.lists'))->badge()->color('gray')->limitList(3),
                TextColumn::make('source')->label(__('newsletter::newsletter.fields.source'))->toggleable(),
                TextColumn::make('created_at')->label(__('newsletter::newsletter.fields.joined'))->date()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('newsletter::newsletter.fields.status'))->options(self::statusOptions()),
                SelectFilter::make('list')->label(__('newsletter::newsletter.list'))->options(fn (): array => self::listOptions())
                    ->query(fn (Builder $q, array $data) => filled($data['value'] ?? null) ? $q->whereHas('lists', fn (Builder $l) => $l->whereKey($data['value'])->whereNull('newsletter_list_subscriber.unsubscribed_at')) : $q),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('unsubscribe')->label(__('newsletter::newsletter.unsubscribe.action'))->icon(Heroicon::OutlinedNoSymbol)->color('danger')->requiresConfirmation()
                    ->visible(fn (Subscriber $record): bool => $record->status !== 'unsubscribed' && (bool) auth()->user()?->can('update', $record))
                    ->action(fn (Subscriber $record) => $record->unsubscribe(__('newsletter::newsletter.unsubscribe.by_admin'))),
                EditAction::make(), DeleteAction::make(), RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscribers::route('/'),
            'create' => CreateSubscriber::route('/create'),
            'edit' => EditSubscriber::route('/{record}/edit'),
        ];
    }
}
