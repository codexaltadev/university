<?php

namespace Platform\Filament\Hub;

use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\PageRegistration;
use Platform\Registry\Registry;

/**
 * Entity Hub (FR-GEN-86, SAD Part A §7.3, UI/UX Part A §4.3): a resource of a microsite
 * entity (Faculty, Department, Program, Office …) gets its record sub-navigation — the
 * Profile (edit page) plus every part other modules register with
 *   $r->entityPage(Faculty::class, FacultyDepartments::class)   // this model
 *   $r->entityPage('microsite', SomePage::class)                // every microsite entity
 * The resource never knows those modules; an inactive module's part is not registered.
 *
 *   class FacultyResource extends Resource { use EntityHub; … hubBasePages(), hubProfilePage() }
 */
trait EntityHub
{
    /** @return array<string, PageRegistration> the resource's own pages: index, create, edit … */
    abstract protected static function hubBasePages(): array;

    /** @return class-string<Page> the edit page, shown as "Profile" */
    abstract protected static function hubProfilePage(): string;

    public static function getPages(): array
    {
        $pages = static::hubBasePages();

        foreach (static::hubPageClasses() as $class) {
            $pages[$class::hubKey()] = $class::route('/{record}/'.$class::hubKey());
        }

        return $pages;
    }

    /** @return list<class-string<HubPage>> registered parts of this Hub, in their order */
    public static function hubPageClasses(): array
    {
        $registry = app(Registry::class);
        $model = static::getModel();
        $isMicrosite = collect($registry->all('micrositeEntity'))->contains(fn (array $entry): bool => $entry['model'] === $model);

        return collect($registry->all('entityPage'))
            ->filter(fn (array $entry): bool => ($entry['target'] === $model || ($entry['target'] === 'microsite' && $isMicrosite))
                && class_exists($entry['class']) && static::class === $entry['class']::getResource())
            ->pluck('class')
            ->sortBy(fn (string $class): int => $class::getNavigationSort() ?? 50)
            ->values()->all();
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([static::hubProfilePage(), ...static::hubPageClasses()]);
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Start;
    }
}
