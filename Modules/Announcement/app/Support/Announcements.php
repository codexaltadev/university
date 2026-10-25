<?php

namespace Modules\Announcement\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Modules\Announcement\Models\Widget;
use Platform\Context\SiteContext;
use Platform\Relations\RelationEngine;
use Throwable;

/**
 * What the layout slots show (FR-ANW-01..07): "announcement" — bars and countdowns; "ticker" —
 * ticker lines (+ the latest notices when asked); "popup" — the one popup of highest priority
 * (FR-ANW-05). Only widgets shown in this site's place, on this page, from / until. Loaded once
 * per request.
 */
class Announcements
{
    public function __construct(protected RelationEngine $engine, protected CurrentPage $page) {}

    /** @return Collection<int, Widget> visible widgets of this place and page, in their order */
    public function here(SiteContext $context): Collection
    {
        $key = $context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY;
        $page = $this->page->key();
        $path = $this->page->path();

        // Kept on the request itself: the three slots share one query, the next request starts fresh
        $memo = 'announcement.widgets.'.$key;
        if (! request()->attributes->has($memo)) {
            request()->attributes->set($memo, $this->engine->for($key)->content(Widget::class)->query()->reorder()->ordered()->get()
                ->filter(fn (Widget $w): bool => $w->showsOn($page, $path) && ! ($w->widget_type === 'countdown' && $w->countdownOver() && $w->after_end === 'hide'))
                ->values());
        }

        return request()->attributes->get($memo);
    }

    /** A kind shows on the website only while it is on in Settings › Announcements (owner 2026-10-25; all off at first) */
    public static function enabled(string $type): bool
    {
        return (bool) settings('announcement.'.$type);
    }

    /** @return list<string> the kinds turned off */
    public static function disabled(): array
    {
        return array_values(array_filter(Widget::TYPES, fn (string $type): bool => ! self::enabled($type)));
    }

    public function slot(string $name, SiteContext $context): ?HtmlString
    {
        try {
            $types = array_values(array_filter(match ($name) {
                'announcement' => ['bar', 'countdown'],
                'ticker' => ['ticker'],
                'popup' => ['popup'],
                default => [],
            }, self::enabled(...)));
            if ($types === []) {
                return null;
            }
            $widgets = $this->here($context)->whereIn('widget_type', $types)->values();
            $html = match ($name) {
                'announcement' => $widgets->isEmpty() ? '' : view('announcement::slots.announcement', ['widgets' => $widgets])->render(),
                'ticker' => ($items = $this->tickerItems($widgets, $context)) === [] ? '' : view('announcement::slots.ticker', ['items' => $items, 'label' => (string) settings('announcement.ticker_label')])->render(),
                'popup' => ($p = $widgets->sortByDesc('priority')->first()) ? view('announcement::slots.popup', ['widget' => $p, 'later' => (string) settings('announcement.popup_later')])->render() : '',
                default => '',
            };

            return trim($html) === '' ? null : new HtmlString($html);
        } catch (Throwable $e) {
            report($e);   // an announcement must never break the page

            return null;
        }
    }

    /**
     * Ticker lines in order; a ticker with "latest notices" adds the place's newest board notices
     * after its own text (FR-ANW-06, Notice is optional).
     *
     * @param  Collection<int, Widget>  $tickers
     * @return list<array{text: string, url: ?string}>
     */
    public function tickerItems(Collection $tickers, SiteContext $context): array
    {
        $items = [];
        $notices = false;
        foreach ($tickers as $ticker) {
            $text = trim(strip_tags((string) ($ticker->body ?: $ticker->title)));
            if ($text !== '') {
                $items[] = ['text' => $text, 'url' => $ticker->href()];
            }
            $notices = $notices || $ticker->include_latest_notices;
        }

        $notice = 'Modules\\Notice\\Models\\Notice';
        if ($notices && class_exists($notice)) {
            $key = $context->isMicrosite() ? $context->key() : RelationEngine::SITE_KEY;
            foreach ($this->engine->for($key)->content($notice)->query()->reorder()->onBoard()->boardOrder()->limit(5)->get() as $n) {
                $items[] = ['text' => (string) $n->title, 'url' => url($n->url())];
            }
        }

        return $items;
    }
}
