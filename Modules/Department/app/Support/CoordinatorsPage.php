<?php

namespace Modules\Department\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Platform\Assignments\PersonView;

/**
 * /coordination-officers ready for a theme (`$page` in department::coordinators), as DIU's /coordination-officer: the
 * coordinators (role "coordinator") of every visible department and of its programs (when the Program module is on),
 * by faculty in its order — each with the program or department they look after. Title, text and picture from
 * Settings › Departments. Themes hold no PHP logic (T-1).
 */
class CoordinatorsPage
{
    public const ROLE = 'coordinator';

    public const PATH = 'coordination-officers';

    /** DIU's icon per faculty (by short name); others get "building" */
    public const ICONS = ['FSIT' => 'zap', 'FBE' => 'award', 'FHSS' => 'star', 'FE' => 'shield', 'FHLS' => 'sparkles', 'FGS' => 'graduation-cap'];

    /** @var list<array<string, mixed>>|null */
    protected ?array $groups = null;

    public function title(): string
    {
        return (string) (settings('department.coordinators_title') ?: __('department::department.coordinators.title'));
    }

    public function subtitle(): ?string
    {
        return filled($text = settings('department.coordinators_subtitle')) ? (string) $text : null;
    }

    public function image(): ?string
    {
        return media_url(settings('department.coordinators_image'), 'large');
    }

    public function url(): string
    {
        return url('/'.self::PATH);
    }

    /**
     * @return list<array{faculty: string, icon: string, people: list<array{name: string, initials: string, photo: ?string, designation: ?string, unit: ?string, email: ?string, phone: ?string, tel: ?string, extension: ?string}>}>
     */
    public function groups(): array
    {
        if ($this->groups !== null) {
            return $this->groups;
        }

        $departments = Department::query()->listed()->with('faculty')->get();
        $programs = $this->programs($departments->pluck('id')->all());
        $position = Faculty::query()->frontendVisible()->ordered()->pluck('id')->flip();

        return $this->groups = $departments
            ->sortBy(fn (Department $d): int => $position[$d->faculty_id] ?? PHP_INT_MAX)
            ->groupBy('faculty_id')
            ->map(function (Collection $list) use ($programs): array {
                $faculty = $list->first()->faculty;

                return [
                    'faculty' => (string) $faculty?->name,
                    'icon' => self::ICONS[strtoupper((string) $faculty?->short_name)] ?? 'building',
                    'people' => $list->flatMap(fn (Department $d): array => [
                        ...$this->cards($d, $d->short_name ?: $d->name),
                        ...collect($programs->get($d->id, []))->flatMap(fn (Model $p): array => $this->cards($p, $p->short_name ?: $p->name))->all(),
                    ])->values()->all(),
                ];
            })
            ->filter(fn (array $group): bool => $group['people'] !== [])
            ->values()->all();
    }

    /** @return list<array<string, mixed>> */
    protected function cards(Model $unit, ?string $unitName): array
    {
        return $unit->people(self::ROLE)->map(fn (PersonView $p): array => [
            'name' => $p->name(),
            'initials' => collect(preg_split('/\s+/', trim(preg_replace('/^(mr|mrs|ms|md|dr)\.?\s+/i', '', $p->name()))))->filter()->take(2)->map(fn (string $w): string => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''),
            'photo' => $p->photoUrl(),
            'designation' => $p->designation(),
            // a label typed on the assignment ("MBA") wins over the unit's short name
            'unit' => filled($p->assignment->getTranslation('role_label', app()->getLocale(), true)) ? $p->roleLabel : $unitName,
            'email' => $p->email(),
            'phone' => $p->phone(),
            'tel' => filled($p->phone()) ? preg_replace('/[^0-9+]/', '', (string) $p->phone()) : null,
            'extension' => $p->extension(),
        ])->values()->all();
    }

    /** @return Collection<int, list<Model>> the visible programs by department id, when the Program module is on */
    protected function programs(array $departmentIds): Collection
    {
        $class = 'Modules\\Program\\Models\\Program';
        if (! class_exists($class) || ! $class::moduleIsActive()) {
            return collect();
        }

        return $class::query()->frontendVisible()->whereIn('department_id', $departmentIds)->ordered()->get()->groupBy('department_id')->map->all();
    }
}
