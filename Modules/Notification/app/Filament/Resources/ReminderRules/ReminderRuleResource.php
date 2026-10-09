<?php

namespace Modules\Notification\Filament\Resources\ReminderRules;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use Modules\Notification\Filament\Resources\ReminderRules\Pages\CreateReminderRule;
use Modules\Notification\Filament\Resources\ReminderRules\Pages\EditReminderRule;
use Modules\Notification\Filament\Resources\ReminderRules\Pages\ListReminderRules;
use Modules\Notification\Models\ReminderRule;
use Modules\Notification\Support\Reminders;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Filament\RelationManagers\ActivityRelationManager;
use UnitEnum;

/** Site › Content reminders (FR-NOTI-09): how fresh each kind of content must stay, per entity type. */
class ReminderRuleResource extends Resource
{
    protected static ?string $model = ReminderRule::class;

    protected static string|UnitEnum|null $navigationGroup = 'Site';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 81;

    protected static ?string $slug = 'reminder-rules';

    public static function getModelLabel(): string
    {
        return __('notification::reminder.rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('notification::reminder.rules');
    }

    public static function form(Schema $schema): Schema
    {
        $reminders = app(Reminders::class);

        return $schema->components([
            Section::make()->columns(2)->columnSpanFull()->schema([
                Select::make('module_slug')->label(__('notification::reminder.fields.module'))
                    ->options(collect($reminders->sources())->map(fn ($source): string => $source::label())->all())
                    ->helperText(__('notification::reminder.help.module'))
                    ->required()->selectablePlaceholder(false),
                Select::make('entity_type')->label(__('notification::reminder.fields.entity_type'))
                    ->options($reminders->entityTypes())->required()->selectablePlaceholder(false)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                        ->where('module_slug', $get('module_slug'))->whereNull('deleted_at')),
                TextInput::make('max_days_without_update')->label(__('notification::reminder.fields.max_days'))
                    ->numeric()->minValue(1)->maxValue(3650)->default(90)->required()->suffix(__('notification::reminder.days_suffix')),
                TextInput::make('remind_every_days')->label(__('notification::reminder.fields.every_days'))
                    ->numeric()->minValue(1)->maxValue(365)->default(7)->required()->suffix(__('notification::reminder.days_suffix')),
                Radio::make('check_mode')->label(__('notification::reminder.fields.check_mode'))
                    ->options(['latest' => __('notification::reminder.modes.latest'), 'each_record' => __('notification::reminder.modes.each_record')])
                    ->default('latest')->required(),
                Toggle::make('is_mandatory')->label(__('notification::reminder.fields.mandatory'))
                    ->helperText(__('notification::reminder.help.mandatory'))->default(true),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
            ]),
            Section::make(__('notification::reminder.messages'))->description(__('notification::reminder.help.messages'))->columnSpanFull()->schema([
                TranslatableTabs::make()->schema(fn (string $locale): array => [
                    Textarea::make("empty_message.{$locale}")->label(__('notification::reminder.fields.empty_message'))->rows(2)->maxLength(1000),
                    Textarea::make("stale_message.{$locale}")->label(__('notification::reminder.fields.stale_message'))->rows(2)->maxLength(1000),
                    Textarea::make("admin_empty_message.{$locale}")->label(__('notification::reminder.fields.admin_empty_message'))->rows(2)->maxLength(1000),
                    Textarea::make("admin_stale_message.{$locale}")->label(__('notification::reminder.fields.admin_stale_message'))->rows(2)->maxLength(1000),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $reminders = app(Reminders::class);

        return $table
            ->columns([
                TextColumn::make('module_slug')->label(__('notification::reminder.fields.module'))
                    ->formatStateUsing(fn (string $state): string => isset($reminders->sources()[$state]) ? $reminders->sources()[$state]::label() : $state.' ⚠')
                    ->description(fn (ReminderRule $record): ?string => isset($reminders->sources()[$record->module_slug]) ? null : __('notification::reminder.module_off')),
                TextColumn::make('entity_type')->label(__('notification::reminder.fields.entity_type'))
                    ->formatStateUsing(fn (string $state): string => $reminders->entityTypes()[$state] ?? $state),
                TextColumn::make('max_days_without_update')->label(__('notification::reminder.fields.max_days'))->numeric(),
                TextColumn::make('remind_every_days')->label(__('notification::reminder.fields.every_days'))->numeric(),
                TextColumn::make('check_mode')->label(__('notification::reminder.fields.check_mode'))->badge()
                    ->formatStateUsing(fn (string $state): string => __("notification::reminder.modes.{$state}")),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [ActivityRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReminderRules::route('/'),
            'create' => CreateReminderRule::route('/create'),
            'edit' => EditReminderRule::route('/{record}/edit'),
        ];
    }
}
