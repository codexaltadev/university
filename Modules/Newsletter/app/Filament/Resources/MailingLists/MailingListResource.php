<?php

namespace Modules\Newsletter\Filament\Resources\MailingLists;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Modules\Newsletter\Filament\Resources\MailingLists\Pages\ManageMailingLists;
use Modules\Newsletter\Models\MailingList;
use Platform\Filament\Components\TranslatableTabs;
use UnitEnum;

/** Communication › Newsletter lists (FR-NLT-04): key, name, description; a public one is offered on the form. */
class MailingListResource extends Resource
{
    protected static ?string $model = MailingList::class;

    protected static ?string $slug = 'newsletter-lists';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 32;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('newsletter::newsletter.list');
    }

    public static function getPluralModelLabel(): string
    {
        return __('newsletter::newsletter.lists');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("name.{$locale}")->label(__('newsletter::newsletter.fields.list_name'))->required($isDefault)->maxLength(150),
                Textarea::make("description.{$locale}")->label(__('newsletter::newsletter.fields.description'))->rows(2)->maxLength(500),
            ]),
            Grid::make(3)->columnSpanFull()->schema([
                TextInput::make('key')->label(__('newsletter::newsletter.fields.key'))->alphaDash()->maxLength(50)->helperText(__('newsletter::newsletter.help.key')),
                Toggle::make('is_public')->label(__('newsletter::newsletter.fields.is_public'))->default(true)->inline(false),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['subscribers as members_count' => fn (Builder $q) => $q->where('status', 'subscribed')->whereNull('newsletter_list_subscriber.unsubscribed_at')]))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('newsletter::newsletter.fields.list_name'))->searchable()->description(fn (MailingList $record): string => $record->key),
                TextColumn::make('members_count')->label(__('newsletter::newsletter.subscribed')),
                IconColumn::make('is_public')->label(__('newsletter::newsletter.fields.is_public'))->boolean(),
                IconColumn::make('is_active')->label(__('platform::relations.active'))->boolean(),
            ])
            ->filters([TrashedFilter::make()])
            ->recordActions([EditAction::make(), DeleteAction::make(), RestoreAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMailingLists::route('/')];
    }
}
