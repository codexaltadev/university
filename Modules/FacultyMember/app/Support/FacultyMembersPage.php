<?php

namespace Modules\FacultyMember\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Modules\Department\Models\Department;
use Modules\FacultyMember\Models\FacultyMember;

/**
 * What the teachers' pages show, ready for a theme (`$page` in facultymember::directory and the
 * "Faculty Members" homepage section), in DIU's look: the faculties with their teachers on the left
 * (main site), a search with department and designation, and a card per teacher — photo or
 * initials, name, designation, department, contacts and "View Profile" on the faculty website
 * (owner 2026-10-25: profiles are always there). Title, picture and text from Settings › Faculty
 * Members. Themes hold no PHP logic (T-1).
 */
class FacultyMembersPage
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return (string) (settings('faculty-member.title') ?: __('facultymember::facultymember.plural'));
    }

    public function image(): ?string
    {
        return media_url(settings('faculty-member.image'), 'large');
    }

    public function intro(): ?string
    {
        return filled($intro = $this->data['intro'] ?? null) ? Str::sanitizeHtml((string) $intro) : null;
    }

    /** "Meet the teachers of Computer Science and Engineering" on a Faculty / Department site */
    public function subtitle(): ?string
    {
        return ($place = $this->data['place'] ?? null) ? __('facultymember::facultymember.page.subtitle', ['place' => $place->name]) : null;
    }

    public function listingUrl(array $query = []): string
    {
        return url((string) ($this->data['listing'] ?? '/'.FacultyMember::PATH)).($query ? '?'.http_build_query($query) : '');
    }

    /** @return list<array{label: string, url: string}> Home, the place, the page */
    public function breadcrumbs(): array
    {
        $place = $this->data['place'] ?? null;

        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            $place ? ['label' => (string) ($place->short_name ?: $place->name), 'url' => url($place->url())] : null,
            ['label' => $this->title(), 'url' => $this->listingUrl()],
        ]));
    }

    /**
     * The faculties with their teachers on the main site (one chosen by ?faculty=), for the sidebar.
     *
     * @return list<array{id: string, label: string, url: string, current: bool, arrow: bool}>
     */
    public function sections(): array
    {
        $faculties = $this->data['faculties'] ?? collect();
        if ($faculties->isEmpty()) {
            return [];
        }
        // Teachers per department from our own table, then per faculty through the Department model (no foreign tables, T-arch)
        $perDepartment = FacultyMember::query()->frontendVisible()->reorder()->whereNotNull('department_id')
            ->selectRaw('department_id, count(*) as people')->groupBy('department_id')->pluck('people', 'department_id');
        $counts = Department::query()->withoutGlobalScopes()->whereKey($perDepartment->keys())->pluck('faculty_id', 'id')
            ->groupBy(fn ($facultyId) => $facultyId, preserveKeys: true)->map(fn ($departments) => $departments->keys()->sum(fn ($id) => (int) $perDepartment[$id]));
        $asked = (string) request()->query('faculty');

        return [
            ['id' => 'all', 'label' => __('facultymember::facultymember.all').' ('.$counts->sum().')', 'url' => $this->listingUrl(), 'current' => $asked === '', 'arrow' => false],
            ...$faculties->filter(fn ($f): bool => ($counts[$f->id] ?? 0) > 0)->map(fn ($f): array => [
                'id' => (string) $f->short_name,
                'label' => ($f->short_name ?: $f->name).' ('.$counts[$f->id].')',
                'url' => $this->listingUrl(['faculty' => $f->short_name]),
                'current' => strcasecmp($asked, (string) $f->short_name) === 0,
                'arrow' => false,
            ])->values()->all(),
        ];
    }

    /** @return array<string, string> department short name => name (none on a Department site) */
    public function departmentOptions(): array
    {
        return collect($this->data['departments'] ?? [])->mapWithKeys(fn (Department $d): array => [(string) $d->short_name => (string) $d->name])->all();
    }

    /** @return array<int, string> designation term id => name, in the ladder's order */
    public function designationOptions(): array
    {
        return collect($this->data['designations'] ?? [])->mapWithKeys(fn ($t): array => [(int) $t->id => (string) $t->name])->all();
    }

    public function total(): int
    {
        return (int) ($this->data['members'] ?? null)?->total();
    }

    /**
     * @return list<array{name: string, designation: ?string, department: ?string, department_url: ?string, photo: ?string, initials: string,
     *     email: ?HtmlString, phone: ?string, tel: ?string, extension: ?string, room: ?string, url: ?string, on_leave: bool}>
     */
    public function cards(): array
    {
        $members = $this->data['members'] ?? $this->data['items']['members'] ?? [];

        return collect(method_exists($members, 'items') ? $members->items() : $members)->map(fn (FacultyMember $m): array => $this->card($m))->values()->all();
    }

    public function card(FacultyMember $member): array
    {
        $person = $member->personPresenter();
        $phone = $person->phone();
        $department = $member->department;

        return [
            'name' => $person->name(),
            'designation' => $person->designation(),
            'department' => $department ? (string) $department->name : null,
            'department_url' => $department && ($this->data['place'] ?? null) === null ? url($department->url()) : null,
            'photo' => $person->photoUrl(),
            'initials' => $this->initials($person->name()),
            'email' => $person->protectedEmailLink(),
            'phone' => $phone,
            'tel' => $phone ? preg_replace('/[^\d+]/', '', $phone) : null,
            'extension' => $person->extension(),
            'room' => $member->office_room ?: null,
            'url' => $person->profileUrl(),
            'on_leave' => $member->isOnLeave(),
        ];
    }

    /** "Dr. Arif Alam" → "AA" (titles left out) */
    protected function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim((string) preg_replace('/^(dr|prof|professor|mr|mrs|ms|engr)\.?\s+/i', '', $name))) ?: []));
        if ($words === []) {
            return '?';
        }

        return Str::upper(Str::substr($words[0], 0, 1).(count($words) > 1 ? Str::substr(end($words), 0, 1) : ''));
    }
}
