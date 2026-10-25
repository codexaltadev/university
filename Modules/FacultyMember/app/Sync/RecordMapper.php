<?php

namespace Modules\FacultyMember\Sync;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Localization\Languages;

/**
 * One raw record → this module's fields (record format: DB-03 §8.3). Texts may be a string
 * (the default language) or {"en": …, "bn": …}. A few other common names are understood too
 * (employeeID, position, picture, cell, status), so the real API needs few changes here.
 */
class RecordMapper
{
    /** @var array<string, list<string>> our key => names accepted in a record, first wins */
    public const ALIASES = [
        'id' => ['id', 'external_id', 'teacher_id'],
        'source_path' => ['source_path'],
        'employee_id' => ['employee_id', 'employeeID'],
        'name' => ['name'],
        'designation' => ['designation', 'position'],
        'designation_note' => ['designation_note'],
        'department' => ['department', 'department_short_name'],
        'department_name' => ['department_name'],
        'other_departments' => ['other_departments'],
        'email' => ['email'],
        'phone' => ['phone', 'cell'],
        'extension' => ['extension', 'ext'],
        'office_room' => ['office_room', 'room'],
        'photo_url' => ['photo_url', 'photo', 'picture'],
        'employment_type' => ['employment_type'],
        'status' => ['status', 'is_active'],
        'biography' => ['biography', 'bio'],
        'research_interests' => ['research_interests', 'research_interest'],
        'links' => ['links'],
        'profile' => ['profile', 'profile_items'],
    ];

    protected ?array $locales = null;

    /**
     * @return array{external_ref: string, department: ?string, other_departments: list<string>, designation: ?string, attributes: array<string, mixed>}
     *
     * @throws InvalidArgumentException why the record cannot be used
     */
    public function map(array $raw): array
    {
        $id = $this->text($this->pick($raw, 'id'));
        $name = $this->translations($this->pick($raw, 'name'));
        if ($id === null) {
            throw new InvalidArgumentException(__('facultymember::facultymember.sync.no_id'));
        }
        if ($name === null) {
            throw new InvalidArgumentException(__('facultymember::facultymember.sync.no_name'));
        }
        $email = $this->text($this->pick($raw, 'email'));

        return [
            'external_ref' => Str::limit($id, 100, ''),
            'department' => $this->text($this->pick($raw, 'department')),
            'department_name' => $this->text($this->pick($raw, 'department_name')),
            'other_departments' => array_values(array_unique(array_filter(array_map(fn ($d) => $this->text($d), (array) $this->pick($raw, 'other_departments'))))),
            'designation' => $this->text($this->pick($raw, 'designation')),
            'attributes' => [
                'external_ref' => Str::limit($id, 100, ''),
                'source_path' => ($s = $this->text($this->pick($raw, 'source_path'))) !== null ? Str::limit($s, 191, '') : null,
                'employee_id' => ($e = $this->text($this->pick($raw, 'employee_id'))) !== null ? Str::limit($e, 30, '') : null,
                'name' => $name,
                'designation_note' => $this->translations($this->pick($raw, 'designation_note')),
                'email' => $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) ? Str::limit($email, 191, '') : null,
                'phone' => ($p = $this->text($this->pick($raw, 'phone'))) !== null ? Str::limit($p, 50, '') : null,
                'extension' => ($x = $this->text($this->pick($raw, 'extension'))) !== null ? Str::limit($x, 20, '') : null,
                'office_room' => ($r = $this->text($this->pick($raw, 'office_room'))) !== null ? Str::limit($r, 100, '') : null,
                'photo_url' => $this->url($this->pick($raw, 'photo_url')),
                'employment_type' => $this->employmentType($this->pick($raw, 'employment_type')),
                'is_active' => $this->active($this->pick($raw, 'status')),
                'biography' => $this->translations($this->pick($raw, 'biography')),
                'research_interests' => $this->translations($this->pick($raw, 'research_interests')),
                'links' => $this->links($this->pick($raw, 'links')),
                'profile_items' => $this->profile($this->pick($raw, 'profile')),
            ],
        ];
    }

    protected function pick(array $raw, string $key): mixed
    {
        foreach (self::ALIASES[$key] as $name) {
            if (array_key_exists($name, $raw) && $raw[$name] !== null && $raw[$name] !== '') {
                return $raw[$name];
            }
        }

        return null;
    }

    protected function text(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[$this->locales()[0]] ?? reset($value);
        }
        if (! is_scalar($value)) {
            return null;
        }
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : $value;
    }

    /** "Text" or {"en": "Text", "bn": "লেখা"} → {"en": "Text", …} (only this site's languages), or null. */
    protected function translations(mixed $value): ?array
    {
        if (is_scalar($value)) {
            $value = [$this->locales()[0] => $value];
        }
        if (! is_array($value)) {
            return null;
        }

        $out = [];
        foreach ($this->locales() as $locale) {
            if (isset($value[$locale]) && is_scalar($value[$locale]) && trim((string) $value[$locale]) !== '') {
                $out[$locale] = trim((string) $value[$locale]);
            }
        }

        return $out === [] ? null : $out;
    }

    protected function url(mixed $value): ?string
    {
        $value = $this->text($value);

        return $value !== null && preg_match('~^https?://~i', $value) && strlen($value) <= 500 ? $value : null;
    }

    protected function employmentType(mixed $value): string
    {
        $value = Str::of((string) $this->text($value))->lower()->replace(['-', ' '], '_')->toString();

        return in_array($value, FacultyMember::EMPLOYMENT_TYPES, true) ? $value : 'full_time';
    }

    /** Missing = active; false / 0 / "inactive" … = not shown (BRL-FM-02). */
    protected function active(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }
        if (is_bool($value) || is_int($value)) {
            return (bool) $value;
        }

        return ! in_array(strtolower(trim((string) $value)), ['0', 'false', 'inactive', 'disabled', 'no', 'left', 'retired'], true);
    }

    /** @return array<string, string>|null */
    protected function links(mixed $value): ?array
    {
        $links = [];
        foreach (FacultyMember::LINKS as $key) {
            if (is_array($value) && ($url = $this->url($value[$key] ?? null))) {
                $links[$key] = $url;
            }
        }

        return $links === [] ? null : $links;
    }

    /** @return array<string, list<array<string, mixed>>>|null */
    protected function profile(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $out = [];
        foreach (FacultyMember::PROFILE_SECTIONS as $section) {
            $items = [];
            foreach ((array) ($value[$section] ?? []) as $item) {
                $item = is_string($item) ? ['title' => $item] : $item;
                if (! is_array($item) || ($title = $this->text($item['title'] ?? null)) === null) {
                    continue;
                }
                $items[] = array_filter([
                    'title' => $title,
                    'institution' => $this->text($item['institution'] ?? null),
                    'start_year' => $this->year($item['start_year'] ?? null),
                    'end_year' => $this->year($item['end_year'] ?? null),
                    'description' => $this->text($item['description'] ?? null),
                    'url' => $this->url($item['url'] ?? null),
                ], fn ($v) => $v !== null);
            }
            if ($items !== []) {
                $out[$section] = $items;
            }
        }

        return $out === [] ? null : $out;
    }

    protected function year(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value >= 1900 && (int) $value <= 2200 ? (int) $value : null;
    }

    /** @return list<string> default language first */
    protected function locales(): array
    {
        if ($this->locales === null) {
            $languages = app(Languages::class);
            $this->locales = array_values(array_unique([$languages->default(), ...$languages->codes()]));
        }

        return $this->locales;
    }
}
