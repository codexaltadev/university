<?php

namespace Modules\Tuition\Filament\Parts;

use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Program\Models\Program;
use Modules\Tuition\Filament\Resources\Tuitions\TuitionResource;
use Modules\Tuition\Models\Tuition;
use Platform\Filament\Hub\EntityFormPart;

/**
 * "Tuition fees" at the end of the Program editor (UI/UX Part A §5.2.1): the program's
 * tuitions, read only — the tuition team edits them in Academic › Tuition Fees. Who may
 * add or edit tuitions gets the links there.
 */
class ProgramTuitionPart extends EntityFormPart
{
    public static function label(): string
    {
        return __('tuition::tuition.tuition_fees');
    }

    public static function icon(): ?string
    {
        return 'heroicon-o-banknotes';
    }

    public static function canView(Model $record): bool
    {
        return (bool) Auth::user()?->can('viewAny', Tuition::class);
    }

    public static function badge(Model $record): ?string
    {
        return (string) Tuition::query()->where('program_id', $record->getKey())->count();
    }

    public static function schema(): array
    {
        $user = fn () => Auth::user();

        return [
            Actions::make([
                Action::make('addTuition')->label(__('tuition::tuition.add'))->icon(Heroicon::OutlinedPlus)
                    ->url(fn (Program $record): string => TuitionResource::getUrl('create', ['program' => $record->getKey()]))
                    ->visible(fn (): bool => (bool) $user()?->can('create', Tuition::class)),
                Action::make('openTuitions')->label(__('tuition::tuition.open_menu'))->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                    ->url(fn (): string => TuitionResource::getUrl('index')),
            ]),
            RepeatableEntry::make('tuitions')->hiddenLabel()
                ->placeholder(__('tuition::tuition.none'))
                ->table([
                    TableColumn::make(__('tuition::tuition.fields.category')),
                    TableColumn::make(__('tuition::tuition.fields.credits')),
                    TableColumn::make(__('tuition::tuition.fields.tuition_total')),
                    TableColumn::make(__('tuition::tuition.fields.total')),
                    TableColumn::make(__('tuition::tuition.fields.active')),
                ])
                ->schema([
                    TextEntry::make('category.name')
                        ->state(fn (Tuition $record): string => trim($record->category?->name.(filled($record->label) ? ' · '.$record->label : '')))
                        ->url(fn (Tuition $record): ?string => $user()?->can('update', $record) ? TuitionResource::getUrl('edit', ['record' => $record]) : null),
                    TextEntry::make('credit_min')->state(fn (Tuition $record): ?string => $record->creditsText())->placeholder('—'),
                    TextEntry::make('tuition_min')->state(fn (Tuition $record): ?string => static::range($record, 'tuition'))->placeholder('—'),
                    TextEntry::make('total_min')->state(fn (Tuition $record): ?string => static::range($record, 'total'))->placeholder('—'),
                    TextEntry::make('is_active')->state(fn (Tuition $record): string => $record->is_active ? '✓' : '—'),
                ]),
        ];
    }

    protected static function range(Tuition $tuition, string $field): ?string
    {
        $min = $tuition->{"{$field}_min"};
        if ($min === null) {
            return null;
        }
        $max = $tuition->{"{$field}_max"};

        return $tuition->category?->money($min).($max !== null && (float) $max !== (float) $min ? ' – '.$tuition->category?->money($max) : '');
    }
}
