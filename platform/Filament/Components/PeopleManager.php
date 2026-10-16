<?php

namespace Platform\Filament\Components;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Platform\Assignments\Assignment;
use Platform\Assignments\AssignmentEngine;
use Platform\Assignments\AssignmentRole;
use Platform\Support\MorphMap;

/**
 * People of an assignable record — office, department, committee … (FR-REL-10/11,
 * UI/UX Part A §6): person (PersonPicker), role from the Standard Role Catalog with
 * an optional own label, order (drag), term, public, and contact overrides for this
 * place. Saved through the Assignment engine; a single-holder role (Head, Dean …)
 * may have one current holder only — the form asks to end the previous term.
 *
 *     PeopleManager::make()   // in the form of a model with IsAssignable
 */
class PeopleManager
{
    /** $withNote: a note per person and language, e.g. a committee member's "Nominated by UGC". */
    public static function make(string $name = 'people', bool $withNote = false): Section
    {
        return Section::make(__('platform::people.title'))
            ->collapsible()
            ->schema([
                Repeater::make($name)->hiddenLabel()
                    ->schema([...static::fields(), ...($withNote ? static::noteFields() : [])])
                    ->itemLabel(fn (array $state): ?string => static::itemLabel($state))
                    ->reorderable()->collapsible()->collapsed()->defaultItems(0)
                    ->addActionLabel(__('platform::people.add'))
                    ->rules([fn (Get $get, ?Model $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                        foreach (static::singleHolderConflicts((array) $value, $record) as $role) {
                            $fail(__('platform::people.one_holder', ['role' => $role]));
                        }
                    }])
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Repeater $component, ?Model $record): void {
                        if ($record?->exists && method_exists($record, 'assignments')) {
                            $component->state(static::load($record));
                        }
                    })
                    ->saveRelationshipsUsing(fn (Model $record, ?array $state) => static::save($record, (array) $state)),
            ]);
    }

    /** @return array<Component|Field> */
    protected static function fields(): array
    {
        return [
            Hidden::make('id'),
            Grid::make(2)->schema([
                PersonPicker::make('person')->label(__('platform::people.person'))->required(),
                Select::make('role_key')->label(__('platform::people.role'))
                    ->options(fn (?Model $record, Select $component): array => static::roleOptions($record ?? $component->getModel()))   // create page: the form's model class
                    ->required()->live(),
            ]),
            Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("role_label.{$locale}")->label(__('platform::people.role_label')." ({$language})")
                    ->helperText($isDefault ? __('platform::people.role_label_help') : null)->maxLength(100),
            ])),
            Grid::make(3)->schema([
                DatePicker::make('starts_on')->label(__('platform::people.starts_on')),
                DatePicker::make('ends_on')->label(__('platform::people.ends_on'))->afterOrEqual('starts_on'),
                Toggle::make('is_public')->label(__('platform::people.is_public'))->default(true),
            ]),
            Grid::make(3)->schema([
                TextInput::make('overrides.phone')->label(__('platform::people.phone'))->tel()->maxLength(50),
                TextInput::make('overrides.extension')->label(__('platform::people.extension'))->maxLength(20),
                TextInput::make('overrides.email')->label(__('platform::people.email'))->email()->maxLength(150),
            ]),
        ];
    }

    /** @return array<Component|Field> */
    protected static function noteFields(): array
    {
        return [
            Grid::make(2)->schema(TranslatableTabs::inline(fn (string $locale, bool $isDefault, string $language): array => [
                TextInput::make("note.{$locale}")->label(__('platform::people.note')." ({$language})")
                    ->helperText($isDefault ? __('platform::people.note_help') : null)->maxLength(191),
            ])),
        ];
    }

    /** @return array<string, string> role key => label, active roles of this kind of place */
    public static function roleOptions(Model|string|null $record): array
    {
        if ($record === null || $record === '') {
            return [];
        }

        return AssignmentRole::query()->where('assignable_type', MorphMap::alias($record))->where('is_active', true)
            ->orderBy('sort_order')->get()
            ->mapWithKeys(fn (AssignmentRole $role): array => [$role->role_key => (string) $role->label])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public static function load(Model $record): array
    {
        return $record->assignments()->orderBy('sort_order')->get()->map(fn (Assignment $a): array => [
            'id' => $a->id,
            'person' => "{$a->person_type}:{$a->person_id}",
            'role_key' => $a->role_key,
            'role_label' => $a->getTranslations('role_label'),
            'starts_on' => $a->starts_on?->toDateString(),
            'ends_on' => $a->ends_on?->toDateString(),
            'is_public' => $a->is_public,
            'overrides' => (array) $a->overrides,
            'note' => $a->getTranslations('note'),
        ])->all();
    }

    /** @param array<int|string, array<string, mixed>> $state */
    public static function save(Model $record, array $state): void
    {
        $engine = app(AssignmentEngine::class);

        DB::transaction(function () use ($record, $state, $engine): void {
            $kept = [];

            foreach (array_values($state) as $position => $row) {
                $person = PersonPicker::resolve($row['person'] ?? null);

                if ($person === null) {
                    continue;
                }

                $attributes = [
                    'sort_order' => $position + 1,
                    'starts_on' => $row['starts_on'] ?? null,
                    'ends_on' => $row['ends_on'] ?? null,
                    'is_public' => (bool) ($row['is_public'] ?? true),
                    'role_label' => array_filter((array) ($row['role_label'] ?? []), fn ($t): bool => filled($t)) ?: null,
                    'overrides' => array_filter((array) ($row['overrides'] ?? []), fn ($v): bool => filled($v)) ?: null,
                ];
                if (array_key_exists('note', $row)) {   // only forms with the note field change it
                    $attributes['note'] = array_filter((array) $row['note'], fn ($t): bool => filled($t)) ?: null;
                }

                $existing = filled($row['id'] ?? null) ? $record->assignments()->find($row['id']) : null;

                if ($existing && $existing->person_type === $person->getMorphClass() && $existing->person_id === $person->getKey() && $existing->role_key === $row['role_key']) {
                    $existing->update($attributes);
                    $kept[] = $existing->id;
                } else {
                    $kept[] = $engine->assign($person, $record, $row['role_key'], $attributes)['assignment']->id;
                }
            }

            $record->assignments()->whereNotIn('id', $kept)->get()->each->delete();
        });
    }

    /**
     * Single-holder roles with more than one current holder in the form.
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return list<string> role labels
     */
    public static function singleHolderConflicts(array $rows, ?Model $record): array
    {
        if ($record === null) {
            return [];
        }

        $single = AssignmentRole::query()->where('assignable_type', MorphMap::alias($record))->where('is_single_holder', true)->pluck('label', 'role_key');
        $today = now()->toDateString();

        return collect($rows)
            ->filter(fn (array $row): bool => isset($single[$row['role_key'] ?? '']) && (blank($row['ends_on'] ?? null) || $row['ends_on'] >= $today))
            ->groupBy('role_key')
            ->filter(fn ($group): bool => $group->count() > 1)
            ->keys()
            ->map(fn (string $key): string => (string) $single[$key])
            ->values()->all();
    }

    protected static function itemLabel(array $state): ?string
    {
        $name = filled($state['person'] ?? null) ? PersonPicker::labelOf($state['person']) : null;
        $former = filled($state['ends_on'] ?? null) && $state['ends_on'] < now()->toDateString();

        return trim(($name ?? '…').' · '.($state['role_key'] ?? '').($former ? ' · '.__('platform::people.former') : '').(($state['is_public'] ?? true) ? '' : ' · '.__('platform::people.hidden')));
    }
}
