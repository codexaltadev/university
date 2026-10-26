<?php

namespace Modules\Faq\Support;

use Illuminate\Support\Str;
use Modules\Faq\Models\Faq;

/**
 * What /faq shows, ready for a theme (`$page` in faq::index), as DIU's /faq: the title (its last word apart, in colour) and
 * line from Settings › FAQ, every category with its count ("All" first) and the one shown, the questions by category with
 * their answers sanitised and the words a search matches. Themes hold no PHP logic (T-1).
 */
class FaqPage
{
    public function __construct(protected array $data) {}

    /** @return array{0: string, 1: string} "Frequently Asked" and "Questions" */
    public function title(): array
    {
        $title = trim((string) (settings('faq.title') ?: __('faq::faq.title')));
        $last = Str::afterLast($title, ' ');

        return $last === $title ? ['', $title] : [Str::beforeLast($title, ' '), $last];
    }

    public function text(): ?string
    {
        return filled($t = settings('faq.text')) ? (string) $t : null;
    }

    public function helpful(): bool
    {
        return (bool) ($this->data['helpful'] ?? false);
    }

    /** @return list<array{label: string, url: string, count: int, current: bool}> "All" and each category that has questions */
    public function categories(): array
    {
        $faqs = $this->data['faqs'] ?? collect();
        [$all] = FaqWebsite::groups($faqs, app()->getLocale());
        $selected = $this->data['selected'] ?? null;
        $base = url()->current();

        return [
            ['label' => __('faq::faq.all'), 'url' => $base, 'count' => $faqs->count(), 'current' => $selected === null],
            ...$all->filter(fn (array $g): bool => $g['category'] !== null)->map(fn (array $g): array => [
                'label' => (string) $g['category']->name,
                'url' => $base.'?category='.urlencode($g['category']->slug()),
                'count' => $g['faqs']->count(),
                'current' => $selected?->is($g['category']) ?? false,
            ])->values()->all(),
        ];
    }

    /** @return list<array{name: string, description: ?string, count: int, faqs: list<array{id: int, anchor: string, question: string, answer: string, search: string, model: Faq}>}> */
    public function groups(): array
    {
        return collect($this->data['groups'] ?? [])->map(fn (array $g): array => [
            'name' => $g['category'] ? (string) $g['category']->name : __('faq::faq.other'),
            'description' => filled($g['category']?->description) ? Str::sanitizeHtml((string) $g['category']->description) : null,
            'count' => $g['faqs']->count(),
            'faqs' => $g['faqs']->map(fn (Faq $f): array => [
                'id' => (int) $f->id,
                'anchor' => 'faq-'.$f->id,
                'question' => (string) $f->question,
                'answer' => Str::sanitizeHtml((string) $f->answer),
                'search' => Str::lower(strip_tags($f->question.' '.$f->answer)),
                'model' => $f,
            ])->values()->all(),
        ])->values()->all();
    }
}
