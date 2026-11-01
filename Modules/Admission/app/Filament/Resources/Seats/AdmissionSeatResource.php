<?php

namespace Modules\Admission\Filament\Resources\Seats;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\Admission\Filament\Clusters\AdmissionCluster;
use Modules\Admission\Filament\Resources\Seats\Pages\ManageAdmissionSeats;
use Modules\Admission\Models\AdmissionSeat;

/** Admission › Seat plan (FR-ADM-17): the admission test's seats, uploaded from a file (replacing the list) and corrected here. */
class AdmissionSeatResource extends Resource
{
    protected static ?string $model = AdmissionSeat::class;

    protected static ?string $cluster = AdmissionCluster::class;

    protected static ?string $slug = 'seats';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?int $navigationSort = 5;

    public static function getModelLabel(): string
    {
        return __('admission::admission.seats.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admission::admission.seats.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('application_serial')->label(__('admission::admission.fields.serial'))->required()->maxLength(50),
            TextInput::make('student_name')->label(__('admission::admission.fields.student'))->required()->maxLength(191),
            TextInput::make('program_label')->label(__('admission::admission.fields.program'))->maxLength(188),
            TextInput::make('building')->label(__('admission::admission.seats.building'))->required()->maxLength(150),
            TextInput::make('room')->label(__('admission::admission.seats.room'))->required()->maxLength(50),
            TextInput::make('floor')->label(__('admission::admission.seats.floor'))->maxLength(50),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('application_serial')
            ->columns([
                TextColumn::make('application_serial')->label(__('admission::admission.fields.serial'))->searchable()->sortable()->copyable(),
                TextColumn::make('student_name')->label(__('admission::admission.fields.student'))->searchable(),
                TextColumn::make('program_label')->label(__('admission::admission.fields.program'))->placeholder('—')->toggleable(),
                TextColumn::make('building')->label(__('admission::admission.seats.building'))->sortable(),
                TextColumn::make('room')->label(__('admission::admission.seats.room'))->sortable(),
                TextColumn::make('floor')->label(__('admission::admission.seats.floor'))->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('building')->label(__('admission::admission.seats.building'))
                    ->options(fn (): array => AdmissionSeat::query()->distinct()->orderBy('building')->pluck('building', 'building')->all()),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAdmissionSeats::route('/')];
    }
}
