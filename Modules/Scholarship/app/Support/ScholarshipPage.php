<?php

namespace Modules\Scholarship\Support;

use Illuminate\Support\Str;
use Modules\Scholarship\Models\Scholarship;

/**
 * What /scholarships and /scholarships/{slug} show, ready for a theme (`$page` in scholarship::listing and ::show), as DIU's
 * /scholarship: hero, "Browse by Section" links and Quick Links from Settings › Scholarships, the text above and below, the
 * scholarships by category as folding items (their details inside); one scholarship's page with its parts. Themes hold no PHP logic (T-1).
 */
class ScholarshipPage
{
    public function __construct(protected array $data = []) {}

    public function title(): string
    {
        return (string) (settings('scholarship.title') ?: __('scholarship::scholarship.plural'));
    }

    public function text(): ?string
    {
        return filled($t = settings('scholarship.text')) ? (string) $t : null;
    }

    public function image(): ?string
    {
        return media_url(settings('scholarship.image'), 'large');
    }

    public function listingUrl(): string
    {
        return url('/'.Scholarship::PATH);
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $last = null, ?string $parent = null, ?string $parentUrl = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => $this->title(), 'url' => $this->listingUrl()],
            $parent ? ['label' => $parent, 'url' => (string) $parentUrl] : null,
            $last ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** @return list<array{id: string, label: string, url: string, current: bool, arrow: bool}> "Browse by Section" (partials/diu/browse-sidebar) */
    public function sideLinks(): array
    {
        $here = rtrim(request()->url(), '/');

        return collect(link_list((array) settings('scholarship.side_links', [])))->values()->map(fn (array $l, int $i): array => [
            'id' => 'link-'.$i,
            'label' => $l['label'],
            'url' => $l['url'],
            'current' => rtrim($l['url'], '/') === $here,
            'arrow' => str_starts_with($l['url'], 'http') && ! str_starts_with($l['url'], url('/')),
        ])->all();
    }

    /** @return list<array{label: string, url: string}> */
    public function quickLinks(): array
    {
        return link_list((array) settings('scholarship.quick_links', []));
    }

    public function intro(): ?string
    {
        return filled($this->data['intro'] ?? null) ? Str::sanitizeHtml((string) $this->data['intro']) : null;
    }

    public function outro(): ?string
    {
        return filled($this->data['outro'] ?? null) ? Str::sanitizeHtml((string) $this->data['outro']) : null;
    }

    /** @return list<array{label: string, items: list<array>}> the scholarships by category (DIU's "Academic Result Based" …), the ones without one last */
    public function groups(): array
    {
        return collect($this->data['groups'] ?? [])->map(fn (array $g): array => [
            'label' => $g['category'] ? (string) $g['category']->name : __('scholarship::scholarship.other'),
            'items' => $g['scholarships']->map(fn (Scholarship $s): array => $this->item($s))->values()->all(),
        ])->values()->all();
    }

    /**
     * One scholarship in a folding list: its text inside, a link to its page when it has more.
     *
     * @return array{id: string, title: string, url: string, summary: ?string, body: ?string, deadline: ?string, closed: bool, calls: ?string, more: bool}
     */
    public function item(Scholarship $s): array
    {
        $body = filled($s->details) ? Str::sanitizeHtml((string) $s->details) : null;

        return [
            'id' => 'scholarship-'.$s->id,
            'title' => (string) $s->title,
            'url' => url($s->url()),
            'summary' => filled($s->summary) ? (string) $s->summary : null,
            'body' => $body,
            'deadline' => $s->deadline_at?->translatedFormat('d F Y'),
            'closed' => $s->isClosed(),
            'calls' => ($n = (int) ($s->parts_count ?? 0)) > 0 ? trans_choice('scholarship::scholarship.parts_count', $n, ['count' => $n]) : null,
            'more' => filled($s->eligibility) || filled($s->how_to_apply) || filled($s->apply_url) || (int) ($s->parts_count ?? 0) > 0 || $body === null,
        ];
    }

    /**
     * One scholarship's page.
     *
     * @return array{title: string, summary: ?string, deadline: ?string, closed: bool, image: ?string, sections: list<array{label: ?string, html: string}>,
     *     apply: ?string, files: list<array{label: string, url: string}>, parts: list<array>, parent: ?array{title: string, url: string}}
     */
    public function scholarship(): array
    {
        /** @var Scholarship $s */
        $s = $this->data['scholarship'];
        $parent = $this->data['parent'] ?? null;

        return [
            'title' => (string) $s->title,
            'summary' => filled($s->summary) ? (string) $s->summary : null,
            'deadline' => $s->deadline_at?->translatedFormat('d F Y'),
            'closed' => $s->isClosed(),
            'image' => $s->firstMedia('image')?->url('large'),
            'sections' => collect(['details', 'eligibility', 'how_to_apply'])->filter(fn (string $f): bool => filled($s->{$f}))
                ->map(fn (string $f): array => ['label' => $f === 'details' ? null : __("scholarship::scholarship.fields.{$f}"), 'html' => Str::sanitizeHtml((string) $s->{$f})])->values()->all(),
            'apply' => $s->apply_url && ! $s->isClosed() ? (string) $s->apply_url : null,
            'files' => $s->mediaIn('attachments')->map(fn ($f): array => ['label' => (string) ($f->title ?: $f->fileName()), 'url' => $f->url()])->values()->all(),
            'parts' => collect($this->data['parts'] ?? [])->map(fn (Scholarship $p): array => $this->item($p))->values()->all(),
            'parent' => $parent ? ['title' => (string) $parent->title, 'url' => url($parent->url())] : null,
        ];
    }
}
