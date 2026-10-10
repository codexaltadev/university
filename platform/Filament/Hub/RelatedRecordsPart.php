<?php

namespace Platform\Filament\Hub;

use Closure;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * A read-only list of an entity's related records at the end of its editor (UI/UX Part A
 * §5.2.1): a Faculty's departments, a Department's programs, a Program's tuition fees.
 * Who may create gets "Add …" (the related form with this entity preset and fixed), who
 * may update a row gets its edit link; everything is edited in the related screen.
 *
 *     class FacultyDepartmentsPart extends RelatedRecordsPart
 *     {   relationship(): 'departments', relatedResource(): DepartmentResource::class,
 *         createKey(): 'faculty', columns(): ['Name' => fn ($d) => $d->name, …] }
 */
abstract class RelatedRecordsPart extends EntityFormPart
{
    abstract protected static function relationship(): string;

    /** @return class-string<resource> */
    abstract protected static function relatedResource(): string;

    /** @return array<string, Closure(Model): ?string> column label => value; the first one links to the record */
    abstract protected static function columns(): array;

    /** The query key the related create page reads to preset this entity (?faculty=3); null: no "Add" here. */
    protected static function createKey(): ?string
    {
        return null;
    }

    public static function label(): string
    {
        return static::relatedResource()::getPluralModelLabel();
    }

    public static function canView(Model $record): bool
    {
        return (bool) Auth::user()?->can('viewAny', static::relatedResource()::getModel());
    }

    public static function badge(Model $record): ?string
    {
        return (string) $record->{static::relationship()}()->count();
    }

    public static function schema(): array
    {
        $resource = static::relatedResource();
        $columns = static::columns();
        $labels = array_keys($columns);
        $values = array_values($columns);

        return [
            Actions::make([
                Action::make('add'.class_basename(static::class))
                    ->label(__('platform::hub.add', ['item' => $resource::getModelLabel()]))->icon(Heroicon::OutlinedPlus)
                    ->url(fn (Model $record): string => $resource::getUrl('create', [static::createKey() => $record->getKey()]))
                    ->visible(fn (): bool => static::createKey() !== null && (bool) Auth::user()?->can('create', $resource::getModel())),
            ]),
            RepeatableEntry::make(static::relationship())->hiddenLabel()
                ->placeholder(__('platform::hub.none'))
                ->table(array_map(fn (string $label): TableColumn => TableColumn::make($label), $labels))
                ->schema(array_map(fn (Closure $value, int $i): TextEntry => TextEntry::make("column_{$i}")
                    ->state(fn (Model $record): ?string => $value($record))
                    ->placeholder('—')
                    ->url($i === 0 ? fn (Model $record): ?string => Auth::user()?->can('update', $record) ? $resource::getUrl('edit', ['record' => $record]) : null : null),
                    $values, array_keys($values))),
        ];
    }
}
