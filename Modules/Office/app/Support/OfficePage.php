<?php

namespace Modules\Office\Support;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeSection;
use Modules\Office\Models\OfficeService;
use Modules\Office\Models\OfficeSupportContact;
use Platform\Assignments\PersonView;
use Platform\Context\MicrositeResolver;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;
use Platform\Settings\Settings;

/**
 * What an office page shows, ready for a theme (`$page` in office::microsite.home and office::team):
 * its own menu's pages for the side list, contact facts, team, FAQs, Quick Links. Themes hold no
 * PHP logic (T-1), so everything worked out lives here.
 */
class OfficePage
{
    /** Shown on the home page; the rest on /team and /faq */
    public const TEAM_ON_HOME = 4;

    public const FAQS_ON_HOME = 5;

    public function __construct(public Office $office) {}

    public function key(): string
    {
        return $this->office->getMorphClass().':'.$this->office->getKey();
    }

    public function url(string $rest = ''): string
    {
        return url(rtrim((string) app(MicrositeResolver::class)->urlFor($this->office), '/').($rest !== '' ? '/'.$rest : ''));
    }

    /**
     * The office's own sidebar menu (else its primary menu), first level, as side links — DIU's
     * registrar-office keeps a long side list and the header short. No main-site fallback: an
     * office without a menu lists Overview only.
     *
     * @return list<array{label: string, url: string, external: bool}>
     */
    public function menu(): array
    {
        $editor = 'Modules\\Menu\\Support\\MenuEditor';
        $tree = 'Modules\\Menu\\Support\\MenuTree';
        $menu = class_exists($editor) ? (app($editor)->own($this->key(), 'sidebar') ?? app($editor)->own($this->key(), 'primary')) : null;
        if ($menu === null) {
            return [];
        }

        return collect(app($tree)->of($menu, app()->getLocale()))
            ->filter(fn (array $item): bool => filled($item['url'] ?? null) && filled($item['title'] ?? null))
            ->reject(fn (array $item): bool => rtrim(url((string) $item['url']), '/') === rtrim($this->url(), '/'))   // "Home" = this page, already Overview
            ->map(fn (array $item): array => [
                'label' => (string) $item['title'],
                'url' => str_starts_with((string) $item['url'], '/') ? url((string) $item['url']) : (string) $item['url'],
                'external' => (bool) ($item['new_tab'] ?? false) || (str_starts_with((string) $item['url'], 'http') && ! str_starts_with((string) $item['url'], url('/'))),
            ])->values()->all();
    }

    /**
     * "Browse by Section" for a page of the office: Overview (the office page), then its menu; the one
     * shown — or the one it sits under — marked current. Only links leaving the site get the ↗.
     *
     * @return list<array{id: string, label: string, url: string, current: bool, arrow: bool, external: bool}>
     */
    public function sidebar(string $current): array
    {
        $current = rtrim(url($current), '/');
        $items = [['label' => __('office::office.page.overview'), 'url' => $this->url(), 'external' => false], ...$this->menu()];
        $at = fn (string $url): bool => ($url = rtrim($url, '/')) === $current || ($url !== rtrim($this->url(), '/') && str_starts_with($current, $url.'/'));

        return collect($items)->map(fn (array $item, int $i): array => [
            'id' => 'section-'.$i, 'label' => $item['label'], 'url' => $item['url'],
            'current' => $at($item['url']), 'arrow' => $item['external'], 'external' => $item['external'],
        ])->values()->all();
    }

    /**
     * The visible pages under a page of the office, for a category page (Employee Service → Recruitment …).
     *
     * @return list<array{title: string, url: string, excerpt: ?string}>
     */
    public function subpages(Model $page): array
    {
        return $page->children()->frontendVisible()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Model $child): array => ['title' => (string) $child->title, 'url' => url($child->url()), 'excerpt' => filled($child->excerpt) ? (string) $child->excerpt : null])
            ->values()->all();
    }

    /** Under the office's name in its hero: "Office of the Registrar - Daffodil International University" (the university's full name, else the site name) */
    public function subtitle(): string
    {
        return __('office::office.page.site_suffix', ['name' => $this->office->name, 'site' => settings('general.university_name') ?: settings('general.site_name')]);
    }

    public function overview(): ?string
    {
        return filled($this->office->overview) ? Str::sanitizeHtml((string) $this->office->overview) : null;
    }

    /** @return array<string, string> location / phone / email / extension / office hours that are filled */
    public function facts(): array
    {
        return array_filter([
            'location' => $this->office->location,
            'phone' => $this->office->phone,
            'email' => $this->office->email,
            'extension' => $this->office->extension,
            'hours' => $this->office->office_hours,
        ], 'filled');
    }

    /** @return array{person: ?PersonView, message: string}|null the Head and their message (FR-OFF-03) */
    public function headMessage(): ?array
    {
        if (blank($this->office->head_message)) {
            return null;
        }

        return ['person' => $this->office->people('head')->first(), 'message' => Str::sanitizeHtml((string) $this->office->head_message)];
    }

    /**
     * The office's active services (FR-OFF-06), texts made safe.
     *
     * @return list<array{id: int, title: string, description: ?string, documents: ?string, fee: ?string, processing_time: ?string, people: Collection<int, PersonView>, apply_url: ?string, form_url: ?string}>
     */
    public function services(): array
    {
        $safe = fn ($html): ?string => filled($html) ? Str::sanitizeHtml((string) $html) : null;

        return $this->memo['services'] ??= $this->office->services()->where('is_active', true)->get()
            ->map(fn (OfficeService $s): array => [
                'id' => $s->id,
                'title' => (string) $s->title,
                'description' => $safe($s->description),
                'documents' => $safe($s->required_documents),
                'fee' => trim(($s->fee_amount !== null ? number_format((float) $s->fee_amount, 2) : '').' '.$s->fee_note) ?: null,
                'processing_time' => filled($s->processing_time) ? (string) $s->processing_time : null,
                'people' => $s->people(OfficeService::ROLE),
                'apply_url' => $s->apply_url,
                'form_url' => $s->firstMedia('form')?->url(),
            ])->values()->all();
    }

    /** @return list<array{name: string, description: ?string, email: ?string, phone: ?string, people: Collection<int, PersonView>}> (FR-OFF-04) */
    public function sections(): array
    {
        return $this->office->sections()->where('is_active', true)->get()
            ->map(fn (OfficeSection $s): array => [
                'name' => (string) $s->name,
                'description' => filled($s->description) ? (string) $s->description : null,
                'email' => $s->email,
                'phone' => $s->phone,
                'people' => $s->people(OfficeSection::ROLE),
            ])->values()->all();
    }

    /** @return Collection<int, PersonView> head first, then the team; someone both head and team once */
    public function team(): Collection
    {
        return $this->memo['team'] ??= $this->office->people(['head', 'team'])
            ->unique(fn (PersonView $p): string => $p->assignment->person_type.':'.$p->assignment->person_id)->values();
    }

    /**
     * The Honour Board: who has headed the office — the head now, then the former ones, latest first —
     * from the office's Head assignments and their terms (role label "Registrar (In charge)" …).
     *
     * @return list<array{name: string, initials: string, label: string, photo: ?string, designation: ?string, from: ?string, to: ?string, years: ?string, current: bool}>
     */
    public function heads(): array
    {
        $row = fn (PersonView $p, bool $current): array => [
            'name' => $p->name(),
            'initials' => static::initials($p->name()),
            'label' => $p->roleLabel,
            'photo' => $p->photoUrl(),
            'designation' => $p->designation(),
            'from' => $p->assignment->starts_on?->translatedFormat('j M Y'),
            'to' => $p->assignment->ends_on?->translatedFormat('j M Y'),
            'years' => $p->assignment->starts_on ? CarbonInterval::make($p->assignment->starts_on->diff($p->assignment->ends_on?->copy()->addDay() ?? now()))
                ->forHumans(['parts' => 2, 'skip' => ['week'], 'minimumUnit' => 'month']) : null,
            'current' => $current,
        ];

        return $this->memo['heads'] ??= $this->office->people('head')->map(fn (PersonView $p): array => $row($p, true))
            ->concat($this->office->people('head', former: true)->sortByDesc(fn (PersonView $p) => $p->assignment->ends_on)->map(fn (PersonView $p): array => $row($p, false)))
            ->values()->all();
    }

    /**
     * The Contact page: the office's facts, the campus it is on (first campus in Settings › Contact:
     * address and map) and who to contact for what.
     *
     * @return array{facts: array<string, string>, campus: ?array{name: string, address: ?string, map: ?string}, contacts: list<array{purpose: string, location: ?string, people: Collection<int, PersonView>}>}
     */
    public function contact(): array
    {
        $settings = app(Settings::class);
        $campus = collect((array) settings('contact.campuses'))->first();

        return [
            'facts' => $this->facts(),
            'campus' => $campus ? [
                'name' => (string) $settings->translate($campus['name'] ?? null),
                'address' => filled($address = $settings->translate($campus['address'] ?? null)) ? (string) $address : null,
                'map' => filled($campus['map_embed'] ?? null) && str_starts_with((string) $campus['map_embed'], 'https://') ? (string) $campus['map_embed'] : null,
            ] : null,
            'contacts' => $this->office->supportContacts()->where('is_active', true)->ordered()->get()
                ->map(fn (OfficeSupportContact $c): array => ['purpose' => (string) $c->purpose, 'location' => filled($c->location) ? (string) $c->location : null, 'people' => $c->people(OfficeSupportContact::ROLE)])
                ->values()->all(),
        ];
    }

    public function faqs(): Collection
    {
        $faq = 'Modules\\Faq\\Models\\Faq';

        return $this->memo['faqs'] ??= class_exists($faq)
            ? app(RelationEngine::class)->for($this->key())->content($faq)->query()->ordered()->get()
            : collect();
    }

    /** @return Collection<int, PersonView> the first few for the home page */
    public function teamPreview(): Collection
    {
        return $this->team()->take(self::TEAM_ON_HOME);
    }

    /** @return list<array{question: string, answer: string}> the first few, answers made safe */
    public function faqPreview(): array
    {
        return $this->faqs()->take(self::FAQS_ON_HOME)
            ->map(fn ($faq): array => ['question' => (string) $faq->question, 'answer' => Str::sanitizeHtml((string) $faq->answer)])
            ->values()->all();
    }

    public function image(): ?MediaItem
    {
        return $this->office->firstMedia('cover');
    }

    /** @return list<array{label: string, url: string}> the office's own, else Settings › Offices' */
    public function quickLinks(): array
    {
        return link_list($this->office->quick_links) ?: link_list(settings('office.page_quick_links'));
    }

    /** @return list<array{icon: string, title: string, text: ?string}> cards such as Mission, Vision (in this language) */
    public function highlights(): array
    {
        $settings = app(Settings::class);

        return collect($this->office->highlights ?? [])
            ->map(fn (array $item): array => [
                'icon' => (string) ($item['icon'] ?? '') ?: 'check',
                'title' => (string) $settings->translate($item['title'] ?? null),
                'text' => filled($text = $settings->translate($item['text'] ?? null)) ? (string) $text : null,
            ])
            ->filter(fn (array $item): bool => $item['title'] !== '')->values()->all();
    }

    /** @return list<array{icon: string, label: string, url: string}> the icon tiles (Quick Service) */
    public function quickServices(): array
    {
        return collect($this->office->quick_services ?? [])
            ->map(fn (array $item): ?array => ($link = link_list([$item])[0] ?? null) ? ['icon' => (string) ($item['icon'] ?? '') ?: 'link', ...$link] : null)
            ->filter()->values()->all();
    }

    /** "MK" for "Dr. Mostafa Kamal": first and last name, titles (Dr., Professor, Engr. …) left out — for a face without a photo */
    public static function initials(string $name): string
    {
        $words = collect(preg_split('/\s+/u', trim($name)) ?: [])
            ->reject(fn (string $w): bool => $w === '' || str_ends_with($w, '.') || in_array(Str::lower($w), ['professor', 'prof', 'dr', 'engr', 'mr', 'ms', 'mrs'], true))
            ->values();

        return Str::upper(collect([$words->first(), $words->count() > 1 ? $words->last() : null])->filter()->map(fn (string $w): string => mb_substr($w, 0, 1))->implode(''));
    }

    protected array $memo = [];
}
