<?php

namespace Modules\Administration\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Administration\Models\Member;

/**
 * What the administration pages show, ready for a theme (`$page` in administration::directory and
 * administration::profile), as DIU's /admin/{section} does: the sections with their numbers on the
 * left (one chosen — the first when nothing is), the Quick Links under them, a search over everyone,
 * and a row per person (photo, name, designation, email, phone, extension). Texts and links come
 * from Settings › Administration. Themes hold no PHP logic (T-1).
 */
class AdministrationPage
{
    public function __construct(protected array $data) {}

    public function title(): string
    {
        return (string) (settings('administration.title') ?: __('administration::administration.plural'));
    }

    public function image(): ?string
    {
        return media_url(settings('administration.image'), 'large');
    }

    public function intro(): ?string
    {
        return filled($intro = $this->data['intro'] ?? settings('administration.intro')) ? Str::sanitizeHtml((string) $intro) : null;
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list(settings('administration.quick_links'));
    }

    public function listingUrl(array $query = []): string
    {
        return url('/'.Member::PATH).($query ? '?'.http_build_query($query) : '');
    }

    public function search(): string
    {
        return (string) ($this->data['search'] ?? '');
    }

    public function searching(): bool
    {
        return $this->search() !== '';
    }

    /** The section shown: the one asked for, the profile's own, or the first with people (none while searching) */
    public function currentId(): ?int
    {
        if (($member = $this->data['member'] ?? null) instanceof Member) {
            return (int) $member->category_id;
        }
        if ($this->searching()) {
            return null;
        }
        $asked = (int) request()->query('category');
        $first = $this->groups()->first();

        return $asked ?: ($first ? (int) $first['category']->id : null);
    }

    /** @return list<array{id: int, label: string, url: string, current: bool, arrow: bool}> sections with people, "Management (5)" */
    public function sections(): array
    {
        $counts = $this->data['counts'] ?? [];
        $current = $this->currentId();

        return collect($this->data['categories'] ?? [])->filter(fn ($c): bool => ($counts[$c->id] ?? 0) > 0)
            ->map(fn ($c): array => [
                'id' => (int) $c->id,
                'label' => $c->name.' ('.$counts[$c->id].')',
                'url' => $this->listingUrl(['category' => $c->id]),
                'current' => $current === (int) $c->id,
                'arrow' => false,
            ])->values()->all();
    }

    /** @return list<array{name: string, designation: ?string, photo: ?string, email: ?string, phone: ?string, tel: ?string, extension: ?string, url: ?string}> */
    public function rows(): array
    {
        $groups = $this->groups();
        $members = $this->searching()
            ? $groups->flatMap(fn (array $g) => $g['members'])
            : ($groups->first(fn (array $g): bool => (int) $g['category']->id === $this->currentId())['members'] ?? collect());

        return $members->map(fn (Member $m): array => $this->row($m))->values()->all();
    }

    /** One person as a row or on their profile; only the contacts they show (FR-ADMN-07) */
    public function row(Member $member): array
    {
        $person = $member->personPresenter();
        $phone = $person->phone();

        return [
            'name' => $person->name(),
            'designation' => $person->designation(),
            'photo' => $person->photoUrl(),
            'email' => $person->email(),
            'phone' => $phone,
            'tel' => $phone ? preg_replace('/[^\d+]/', '', trim(explode(',', $phone)[0])) : null,
            'extension' => $person->extension(),
            'url' => $person->profileUrl(),
        ];
    }

    /** @return array{name: string, designation: ?string, photo: ?string, email: ?string, phone: ?string, tel: ?string, extension: ?string, url: ?string, office: ?string, office_url: ?string, site: string, biography: ?string} */
    public function profile(): array
    {
        $member = $this->data['member'];
        $office = $this->data['office'] ?? null;

        return $this->row($member) + [
            'office' => $office ? (string) $office->name : null,
            'office_url' => $office && method_exists($office, 'url') ? url($office->url()) : null,
            'site' => (string) settings('general.site_name'),
            'biography' => filled($member->biography) ? Str::sanitizeHtml((string) $member->biography) : null,
        ];
    }

    /** @return list<array{place: string, url: ?string, role: string}> */
    public function places(): array
    {
        return collect($this->data['places'] ?? [])->map(fn (array $p): array => [...$p, 'url' => $p['url'] ? url($p['url']) : null])->all();
    }

    public function backUrl(): string
    {
        return $this->listingUrl(($id = $this->currentId()) ? ['category' => $id] : []);
    }

    protected function groups(): Collection
    {
        return collect($this->data['groups'] ?? []);
    }
}
