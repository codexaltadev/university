<?php

namespace Modules\Calendar\Filament\Resources\Calendars\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Calendar\Models\Calendar;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Support\EventImporter;
use Platform\Filament\Components\TermSelect;
use Platform\Filament\Components\TranslatableTabs;
use Platform\Taxonomy\Vocabularies;

/**
 * A calendar's dates (FR-CAL-02/03/08): title, a day or a range (or only words), type and
 * audience; in the calendar's order, dragged to change it; Import from Excel / CSV.
 * Whoever may change the calendar manages them.
 */
class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('calendar::calendar.fields.events');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale, bool $isDefault): array => [
                TextInput::make("title.{$locale}")->label(__('calendar::calendar.fields.event'))->required($isDefault)->maxLength(255),
                TextInput::make("date_label.{$locale}")->label(__('calendar::calendar.fields.date_label'))
                    ->helperText(__('calendar::calendar.help.date_label'))->maxLength(255),
            ]),
            Grid::make(2)->columnSpanFull()->schema([
                DatePicker::make('starts_on')->label(__('calendar::calendar.fields.starts_on'))->native(false)->displayFormat('d M Y')
                    ->helperText(__('calendar::calendar.help.starts_on')),
                DatePicker::make('ends_on')->label(__('calendar::calendar.fields.ends_on'))->native(false)->displayFormat('d M Y')
                    ->afterOrEqual('starts_on')->helperText(__('calendar::calendar.help.ends_on')),
                Select::make('type_term_id')->label(__('calendar::calendar.fields.type'))
                    ->options(fn (): array => app(Vocabularies::class)->options(CalendarEvent::TYPE)),
                TermSelect::make('audience')->vocabulary(CalendarEvent::AUDIENCE)
                    ->label(__('calendar::calendar.fields.audience'))->helperText(__('calendar::calendar.help.audience')),
            ]),
            Toggle::make('is_active')->label(__('platform::relations.active'))->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['type', 'terms']))
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label(__('calendar::calendar.fields.event'))->searchable()->wrap(),
                TextColumn::make('when')->label(__('calendar::calendar.fields.date'))
                    ->state(fn (CalendarEvent $record): string => $record->when())
                    ->color(fn (CalendarEvent $record): ?string => $record->starts_on ? null : 'warning')
                    ->description(fn (CalendarEvent $record): ?string => $record->starts_on ? null : __('calendar::calendar.words_only')),
                TextColumn::make('type.name')->label(__('calendar::calendar.fields.type'))->badge()
                    ->color(fn (CalendarEvent $record): array|string => preg_match('/^#[0-9a-f]{6}$/i', (string) $record->type?->color) ? Color::hex($record->type->color) : 'gray'),
                TextColumn::make('audience')->label(__('calendar::calendar.fields.audience'))->badge()
                    ->state(fn (CalendarEvent $record): array => $record->terms->where('vocabulary_id', app(Vocabularies::class)->find(CalendarEvent::AUDIENCE)->id)->pluck('name')->all())
                    ->placeholder(__('calendar::calendar.everyone')),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->filters([
                SelectFilter::make('undated')->label(__('calendar::calendar.fields.date'))
                    ->options(['dated' => __('calendar::calendar.dated'), 'words' => __('calendar::calendar.words_only')])
                    ->query(fn ($query, array $data) => match ($data['value'] ?? null) {
                        'dated' => $query->whereNotNull('starts_on'),
                        'words' => $query->whereNull('starts_on'),
                        default => $query,
                    }),
            ])
            ->headerActions([
                CreateAction::make()->modalWidth('3xl')
                    ->mutateDataUsing(fn (array $data): array => $data + ['sort_order' => (int) $this->getOwnerRecord()->events()->withTrashed()->max('sort_order') + 1]),
                $this->importAction(),
            ])
            ->recordActions([EditAction::make()->modalWidth('3xl'), DeleteAction::make()]);
    }

    protected function importAction(): Action
    {
        return Action::make('import')->label(__('calendar::calendar.import.action'))->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->visible(fn (): bool => $this->canCreate())
            ->modalDescription(__('calendar::calendar.import.help'))
            ->schema([
                FileUpload::make('file')->label(__('calendar::calendar.import.file'))->required()
                    ->disk('local')->directory('calendar-imports')
                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->maxSize(10240),
                Toggle::make('check_only')->label(__('calendar::calendar.import.check_only'))->default(true),
            ])
            ->action(function (array $data): void {
                /** @var Calendar $calendar */
                $calendar = $this->getOwnerRecord();
                $importer = app(EventImporter::class);
                $file = (string) $data['file'];
                $checked = $importer->check($calendar, Storage::disk('local')->path($file));

                if (! $data['check_only'] && $checked['rows'] !== []) {
                    $importer->save($calendar, $checked['rows']);
                }
                Storage::disk('local')->delete($file);

                Notification::make()->{$checked['errors'] === [] ? 'success' : 'warning'}()
                    ->title(__($data['check_only'] ? 'calendar::calendar.import.checked' : 'calendar::calendar.import.done', ['good' => count($checked['rows']), 'total' => $checked['total']]))
                    ->body(implode("\n", array_slice($checked['errors'], 0, 15)))
                    ->persistent($checked['errors'] !== [])
                    ->send();
            });
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('view', $ownerRecord);
    }

    protected function canCreate(): bool
    {
        return (bool) auth()->user()?->can('update', $this->getOwnerRecord());
    }

    protected function canEdit(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canDelete(Model $record): bool
    {
        return $this->canCreate();
    }

    protected function canReorder(): bool
    {
        return $this->canCreate();
    }
}
