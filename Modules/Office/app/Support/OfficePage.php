<?php

namespace Modules\Office\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Office\Models\Office;
use Modules\Office\Models\OfficeSection;
use Modules\Office\Models\OfficeService;
use Platform\Assignments\PersonView;
use Platform\Context\MicrositeResolver;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;

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
     * The office's own primary menu, first level, as side links (no main-site fallback: an office
     * without a menu lists Overview only).
     *
     * @return list<array{label: string, url: string, external: bool}>
     */
    public function menu(): array
    {
        $editor = 'Modules\\Menu\\Support\\MenuEditor';
        $tree = 'Modules\\Menu\\Support\\MenuTree';
        $menu = class_exists($editor) ? app($editor)->own($this->key(), 'primary') : null;
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

    /** @return Collection<int, PersonView> head first, then the team */
    public function team(): Collection
    {
        return $this->memo['team'] ??= $this->office->people(['head', 'team']);
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

    protected array $memo = [];
}
