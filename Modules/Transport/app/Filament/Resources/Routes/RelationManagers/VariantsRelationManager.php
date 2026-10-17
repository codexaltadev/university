<?php

namespace Modules\Transport\Filament\Resources\Routes\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Transport\Models\Departure;
use Modules\Transport\Models\ServiceType;
use Modules\Transport\Models\Stop;
use Modules\Transport\Models\Variant;
use Platform\Filament\Components\TranslatableTabs;

/**
 * A route's stops and times per service type (FR-TRN-02/04/09): the service, days it does not run,
 * a message for that service; the stops dragged into order (at least two); the departures to and
 * from campus — many at once.
 */
class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('transport::transport.variants');
    }

    /** @return array<string, string> */
    protected static function days(): array
    {
        return collect(Variant::DAYS)->mapWithKeys(fn (string $d): array => [$d => __("transport::transport.days.{$d}")])->all();
    }

    public function form(Schema $schema): Schema
    {
        $stopOptions = fn (): array => Stop::query()->where('is_active', true)->ordered()->get()->mapWithKeys(fn (Stop $s): array => [$s->id => (string) $s->name])->all();

        return $schema->components([
            Grid::make(2)->columnSpanFull()->schema([
                Select::make('service_type_id')->label(__('transport::transport.fields.service_type'))->required()
                    ->options(fn (): array => ServiceType::query()->ordered()->get()->mapWithKeys(fn (ServiceType $t): array => [$t->id => (string) $t->name])->all()),
                Toggle::make('is_active')->label(__('platform::relations.active'))->default(true)->inline(false),
            ]),
            CheckboxList::make('days_off')->label(__('transport::transport.fields.days_off'))->options(self::days())->columns(4)->columnSpanFull(),
            TranslatableTabs::make()->columnSpanFull()->schema(fn (string $locale): array => [
                Textarea::make("message.{$locale}")->label(__('transport::transport.fields.variant_message'))->rows(3)->maxLength(2000),
            ]),
            Section::make(__('transport::transport.fields.stops'))->compact()->columnSpanFull()->schema([
                Repeater::make('stop_rows')->hiddenLabel()->helperText(__('transport::transport.help.stops'))
                    ->schema([Select::make('stop_id')->hiddenLabel()->options($stopOptions)->searchable()->required()])
                    ->reorderable()->defaultItems(2)->minItems(2)->addActionLabel(__('transport::transport.add_stop'))
                    ->itemLabel(fn (array $state): ?string => ($id = $state['stop_id'] ?? null) ? Stop::query()->find($id)?->name : null),
            ]),
            Section::make(__('transport::transport.fields.departures'))->compact()->columnSpanFull()->schema([
                Repeater::make('departure_rows')->hiddenLabel()->helperText(__('transport::transport.help.departures'))
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('direction')->label(__('transport::transport.fields.direction'))->required()->default('to_campus')->selectablePlaceholder(false)
                                ->options(['to_campus' => __('transport::transport.to_campus'), 'from_campus' => __('transport::transport.from_campus')]),
                            TimePicker::make('departs_at')->label(__('transport::transport.fields.departs_at'))->seconds(false)->required(),
                            Select::make('trip_type')->label(__('transport::transport.fields.trip_type'))->default('regular')->selectablePlaceholder(false)
                                ->options(['regular' => __('transport::transport.trip_types.regular'), 'shuttle' => __('transport::transport.trip_types.shuttle')]),
                        ]),
                        CheckboxList::make('days_off')->label(__('transport::transport.fields.departure_days_off'))->options(self::days())->columns(4),
                    ])
                    ->collapsible()->defaultItems(0)->addActionLabel(__('transport::transport.add_departure'))
                    ->itemLabel(fn (array $state): ?string => isset($state['departs_at']) ? ($state['departs_at'].' · '.__('transport::transport.'.($state['direction'] ?? 'to_campus'))) : null),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['serviceType', 'stops', 'departures']))
            ->columns([
                TextColumn::make('serviceType.name')->label(__('transport::transport.fields.service_type')),
                TextColumn::make('stops_count')->label(__('transport::transport.fields.stops'))->state(fn (Variant $record): string => $record->stops->first()?->name.' → '.$record->stops->last()?->name.' ('.$record->stops->count().')'),
                TextColumn::make('times')->label(__('transport::transport.fields.departures'))->wrap()
                    ->state(fn (Variant $record): string => $record->departures->map(fn (Departure $d): string => $d->time())->implode(', ')),
                TextColumn::make('days_off')->label(__('transport::transport.fields.days_off'))->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => __("transport::transport.days.{$state}")),
                ToggleColumn::make('is_active')->label(__('platform::relations.active')),
            ])
            ->headerActions([
                CreateAction::make()->modalWidth('5xl')->using(fn (array $data): Variant => $this->save(new Variant(['route_id' => $this->getOwnerRecord()->getKey()]), $data)),
            ])
            ->recordActions([
                EditAction::make()->modalWidth('5xl')
                    ->mutateRecordDataUsing(fn (array $data, Variant $record): array => $data + [
                        'stop_rows' => $record->stops->map(fn (Stop $s): array => ['stop_id' => $s->id])->all(),
                        'departure_rows' => $record->departures->map(fn (Departure $d): array => ['direction' => $d->direction, 'departs_at' => $d->time(), 'trip_type' => $d->trip_type, 'days_off' => (array) $d->days_off])->all(),
                    ])
                    ->using(fn (Variant $record, array $data): Variant => $this->save($record, $data)),
                Action::make('copy')->label(__('transport::transport.copy_to_service'))->icon('heroicon-o-document-duplicate')->color('gray')
                    ->modalHeading(__('transport::transport.copy_to_service'))
                    ->schema([Select::make('service_type_id')->label(__('transport::transport.fields.service_type'))->required()
                        ->options(fn (): array => ServiceType::query()->ordered()->get()->mapWithKeys(fn (ServiceType $t): array => [$t->id => (string) $t->name])->all())])
                    ->action(fn (Variant $record, array $data) => $this->copy($record, (int) $data['service_type_id'])),
                DeleteAction::make(),
            ]);
    }

    /** The variant, its stops in order and its departures, in one go. */
    protected function save(Variant $variant, array $data): Variant
    {
        return DB::transaction(function () use ($variant, $data): Variant {
            $variant->fill(Arr::only($data, ['service_type_id', 'days_off', 'message', 'is_active']))->save();
            $variant->syncStops(array_column(array_values((array) ($data['stop_rows'] ?? [])), 'stop_id'));
            $variant->departures()->delete();
            foreach (array_values((array) ($data['departure_rows'] ?? [])) as $i => $row) {
                $variant->departures()->create(Arr::only($row, ['direction', 'departs_at', 'trip_type', 'days_off']) + ['sort_order' => $i + 1, 'is_active' => true]);
            }

            return $variant;
        });
    }

    /** The same stops and times for another service (e.g. the exam schedule), to change from there. */
    protected function copy(Variant $variant, int $serviceTypeId): Variant
    {
        $variant->loadMissing(['stops', 'departures']);

        return $this->save(new Variant(['route_id' => $variant->route_id]), [
            'service_type_id' => $serviceTypeId,
            'days_off' => $variant->days_off,
            'message' => $variant->getTranslations('message'),
            'is_active' => true,
            'stop_rows' => $variant->stops->map(fn (Stop $s): array => ['stop_id' => $s->id])->all(),
            'departure_rows' => $variant->departures->map(fn (Departure $d): array => ['direction' => $d->direction, 'departs_at' => $d->time(), 'trip_type' => $d->trip_type, 'days_off' => (array) $d->days_off])->all(),
        ]);
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
}
