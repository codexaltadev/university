<?php

namespace Platform\Filament\Hub;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
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

    /** @return list<class-string<static>> the registered parts of a model, in their order */
    public static function partsFor(string $model): array
    {
        return collect(app(Registry::class)->all('entityFormPart'))
            ->filter(fn (array $entry): bool => $entry['target'] === $model && class_exists($entry['class']))
            ->pluck('class')
            ->sortBy(fn (string $class): int => $class::sort())
            ->values()->all();
    }

    /** @return list<Section> the parts as sections below a form without tabs (Faculty, Department) */
    public static function sectionsFor(string $model): array
    {
        return collect(static::partsFor($model))
            ->map(fn (string $class): Section => Section::make($class::label())
                ->icon($class::icon())
                ->columnSpanFull()->collapsible()
                ->afterHeader(fn (?Model $record): array => $record?->exists && filled($badge = $class::badge($record)) ? [Text::make($badge)->badge()] : [])
                ->visible(fn (?Model $record): bool => $record?->exists === true && $class::canView($record))
                ->schema($class::schema()))
            ->all();
    }

    /** @return list<Tab> the parts as editor tabs (Program) */
    public static function tabsFor(string $model): array
    {
        return collect(static::partsFor($model))
            ->map(fn (string $class): Tab => Tab::make($class::label())
                ->icon($class::icon())
                ->visible(fn (?Model $record): bool => $record?->exists === true && $class::canView($record))
                ->badge(fn (?Model $record): ?string => $record?->exists ? $class::badge($record) : null)
                ->schema($class::schema()))
            ->values()->all();
    }
}
