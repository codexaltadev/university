<?php

namespace Modules\Admission\Filament\Resources\ResultBatches\RelationManagers;

use Filament\Actions\DeleteAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Admission\Models\AdmissionResult;

/** The batch's results — found by serial, name or program; filled by Import, not by hand. */
class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admission::admission.result.plural');
    }

    /** Whoever may see the batch sees its results; whoever may change it may remove one. */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->can('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('application_serial')
            ->columns([
                TextColumn::make('application_serial')->label(__('admission::admission.fields.serial'))->searchable()->sortable(),
                TextColumn::make('student_name')->label(__('admission::admission.fields.student'))->searchable(),
                TextColumn::make('program_label')->label(__('admission::admission.fields.program'))->searchable()->wrap()
                    ->color(fn (AdmissionResult $record): ?string => $record->program_id ? null : 'warning'),
                TextColumn::make('result')->label(__('admission::admission.fields.result'))->badge()
                    ->formatStateUsing(fn (string $state, AdmissionResult $record): string => $record->result_label ?: __("admission::admission.results.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'selected' => 'success', 'waiting' => 'warning', 'not_selected' => 'danger', default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('result')->label(__('admission::admission.fields.result'))
                    ->options(collect(AdmissionResult::RESULTS)->mapWithKeys(fn (string $r): array => [$r => __("admission::admission.results.{$r}")])->all()),
            ])
            ->recordActions([
                DeleteAction::make()->authorize(fn (): bool => (bool) auth()->user()?->can('update', $this->getOwnerRecord())),
            ]);
    }
}
