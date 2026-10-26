<?php

namespace Modules\Newsletter\Support;

use Illuminate\Support\Str;
use Modules\Newsletter\Models\Campaign;
use Platform\Concerns\IsSearchable;

/**
 * What /newsletter and a past issue show, ready for a theme (`$page` in newsletter::index and ::issue): the title, line and
 * picture from Settings › Newsletter, the past issues (subject, date, a few words), one issue's text sanitised. Themes hold
 * no PHP logic (T-1).
 */
class NewsletterPage
{
    public function __construct(protected array $data = []) {}

    public function title(): string
    {
        return (string) (settings('newsletter.title') ?: __('newsletter::newsletter.title'));
    }

    public function intro(): string
    {
        return filled($this->data['intro'] ?? null) ? (string) settings()->translate($this->data['intro']) : __('newsletter::newsletter.footer.default_line');
    }

    public function image(): ?string
    {
        return media_url(settings('newsletter.image'), 'large');
    }

    public function listingUrl(): string
    {
        return url('/'.NewsletterWebsite::PATH);
    }

    /** @return list<array{label: string, url: string}> */
    public function breadcrumbs(?string $last = null): array
    {
        return array_values(array_filter([
            ['label' => __('theme::theme.home'), 'url' => url('/')],
            ['label' => $this->title(), 'url' => $this->listingUrl()],
            $last ? ['label' => $last, 'url' => request()->url()] : null,
        ]));
    }

    /** @return list<array{subject: string, date: string, iso: string, text: string, url: string}> */
    public function issues(): array
    {
        $issues = $this->data['issues'] ?? null;

        return $issues ? collect($issues->items())->map(fn (Campaign $c): array => $this->card($c))->all() : [];
    }

    /** The paginator, for the page numbers (null when Settings hides the issues) */
    public function paginator(): mixed
    {
        return $this->data['issues'] ?? null;
    }

    /** @return array{subject: string, date: string, iso: string, body: string, source: ?array{label: string, url: string}} */
    public function issue(): array
    {
        /** @var Campaign $c */
        $c = $this->data['issue'];

        return [
            ...$this->card($c),
            'body' => Str::sanitizeHtml((string) $c->body),
            'source' => filled($c->source_url) ? ['label' => (string) ($c->source_label ?: __('newsletter::newsletter.page.read_more')), 'url' => (string) $c->source_url] : null,
        ];
    }

    /** @return array{subject: string, date: string, iso: string, text: string, url: string} */
    protected function card(Campaign $c): array
    {
        return [
            'subject' => (string) $c->subject,
            'date' => $c->sent_at->translatedFormat('d F Y'),
            'iso' => $c->sent_at->toDateString(),
            'text' => Str::limit(IsSearchable::plainText((string) $c->body), 160),   // words kept apart across tags
            'url' => url('/'.NewsletterWebsite::ISSUES.'/'.$c->id),
        ];
    }
}
