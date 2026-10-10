<?php

namespace Platform\Filament\Hub;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Platform\Registry\Registry;

/**
 * A read-only part another module adds at the end of an entity's editor (UI/UX Part A
 * §5.2.1), registered with $r->entityFormPart(Program::class, MyPart::class). It shows
 * that module's data about the record (e.g. a program's tuition fees); the data is
 * edited in the module's own menu. Shown on existing records only, to who may see it.
 *
 *     class ProgramTuitionPart extends EntityFormPart { label(), schema(), canView() … }
 */
abstract class EntityFormPart
{
    abstract public static function label(): string;

    /** @return array<Component> read-only components; the record is the entity */
    abstract public static function schema(): array;

    public static function icon(): ?string
    {
        return null;
    }

    public static function sort(): int
    {
        return 100;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    public static function badge(Model $record): ?string
    {
        return null;
    }

    /** @return list<Tab> the registered parts of a model, in their order, as editor tabs */
    public static function tabsFor(string $model): array
    {
        return collect(app(Registry::class)->all('entityFormPart'))
            ->filter(fn (array $entry): bool => $entry['target'] === $model && class_exists($entry['class']))
            ->pluck('class')
            ->sortBy(fn (string $class): int => $class::sort())
            ->map(fn (string $class): Tab => Tab::make($class::label())
                ->icon($class::icon())
                ->visible(fn (?Model $record): bool => $record?->exists === true && $class::canView($record))
                ->badge(fn (?Model $record): ?string => $record?->exists ? $class::badge($record) : null)
                ->schema($class::schema()))
            ->values()->all();
    }
}
