<?php

namespace Modules\Page\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Modules\Page\Blocks\HtmlBlock;

/**
 * What the key facts block shows, ready for a theme (`$facts` in page::blocks.facts, UI/UX Part B §5.8 v0.6):
 * the numbers on top (split for counting up), the chapters and their facts as tiles, the note. Each fact
 * keeps its approved text; only its shape is found here, from the value unless the admin picked one:
 * a table of numbers → bars, a value that opens with a number → a big number (the number lifted out of
 * the text), a list with years → a timeline newest first, a long list → chips, anything else → text.
 * Themes hold no PHP logic (T-1).
 */
class FactsPage
{
    /** A number as written: 2002, 23,972, 30+, 1,140,500 */
    public const NUMBER = '/\d[\d,]*(?:\.\d+)?\+?/u';

    public const YEAR = '/\b(?:19|20)\d{2}\b/';

    /** Chapters from which the chapter bar shows */
    public const NAV_FROM = 2;

    /** List length from which a list shows as chips */
    public const CHIPS_FROM = 8;

    /** @var list<array>|null */
    protected ?array $chapters = null;

    public function __construct(protected array $data) {}

    /**
     * The numbers on top, the leading figure apart so a theme can count up to it.
     *
     * @return list<array{label: string, count: ?int, from: int, number: string, rest: string}>
     */
    public function highlights(): array
    {
        return collect((array) ($this->data['highlights'] ?? []))
            ->filter(fn ($h): bool => filled($h['value'] ?? null))
            ->map(function (array $h): array {
                $value = trim((string) $h['value']);
                preg_match('/^(\d[\d,]*)(.*)$/su', $value, $m);
                $count = isset($m[1]) ? (int) str_replace(',', '', $m[1]) : null;

                return [
                    'label' => (string) ($h['label'] ?? ''),
                    'count' => $count,
                    'from' => $count !== null && $count >= 1900 && $count <= 2100 ? $count - 12 : 0,   // a year counts up from a little before
                    'number' => $m[1] ?? $value,
                    'rest' => $m[2] ?? '',
                ];
            })
            ->values()->all();
    }

    /**
     * The chapters and their facts. A block saved before chapters (rows only) is one chapter without a title.
     *
     * @return list<array{id: string, title: ?string, tiles: list<array>}>
     */
    public function chapters(): array
    {
        if ($this->chapters !== null) {
            return $this->chapters;
        }
        $chapters = filled($this->data['chapters'] ?? null) ? (array) $this->data['chapters'] : [['title' => null, 'rows' => (array) ($this->data['rows'] ?? [])]];
        $ids = [];

        return $this->chapters = collect($chapters)
            ->map(fn (array $chapter, int $i): array => [
                'id' => $this->unique(Str::slug((string) ($chapter['title'] ?? '')) ?: 'facts-'.($i + 1), $ids),
                'title' => filled($chapter['title'] ?? null) ? (string) $chapter['title'] : null,
                'tiles' => collect((array) ($chapter['rows'] ?? []))
                    ->filter(fn ($row): bool => filled($row['label'] ?? null))
                    ->map(fn (array $row): array => $this->tile($row, $ids))
                    ->values()->all(),
            ])
            ->filter(fn (array $chapter): bool => $chapter['tiles'] !== [])
            ->values()->all();
    }

    /** Whether the chapter bar shows: the admin's toggle and at least two named chapters */
    public function nav(): bool
    {
        return (bool) ($this->data['show_nav'] ?? true)
            && count(array_filter($this->chapters(), fn (array $c): bool => $c['title'] !== null)) >= self::NAV_FROM;
    }

    public function note(): ?HtmlString
    {
        return filled($this->data['note'] ?? null) ? HtmlBlock::clean($this->data['note']) : null;
    }

    /**
     * One fact as a tile: its label without the trailing colon ("Faculties :" → "Faculties", the stored text
     * unchanged), an anchor, the shape and what that shape needs; size s / m / l / xl = a quarter, a third,
     * half or all of the row.
     *
     * @param  array<string, int>  $ids
     */
    protected function tile(array $row, array &$ids): array
    {
        $label = rtrim(trim((string) $row['label']), " :\u{00A0}");
        $html = (string) HtmlBlock::clean($row['value'] ?? null);
        $display = in_array($row['display'] ?? 'auto', ['text', 'stat', 'chips', 'cards', 'bars', 'timeline'], true) ? $row['display'] : $this->shape($html);
        $tile = ['id' => $this->unique(Str::slug($label) ?: 'fact', $ids), 'label' => $label, 'featured' => (bool) ($row['featured'] ?? false)];

        $shaped = match ($display) {
            'stat' => $this->stat($html),
            'bars' => $this->bars($html),
            'chips', 'cards', 'timeline' => $this->items($html, $display),
            default => null,
        };
        $tile += $shaped ?? ['display' => 'text', 'html' => new HtmlString($html)];
        $tile['size'] = $this->size($tile, $html);

        return $tile;
    }

    /** The shape a value asks for when the admin left it on auto */
    protected function shape(string $html): string
    {
        $text = $this->plain($html);
        if (count(array_filter($this->pairs($html, tableOnly: true), fn (array $p): bool => $p['number'] !== null)) >= 2) {
            return 'bars';
        }
        if (preg_match('/^\d/u', $text) && mb_strlen($text) <= 240) {
            return 'stat';
        }
        [$dom, $body] = $this->dom($html);
        $list = (new DOMXPath($dom))->query('(.//ul|.//ol)[1]', $body)->item(0);
        $items = $list ? $this->children($list, 'li') : [];
        $dated = count(array_filter($items, fn (DOMElement $li): bool => (bool) preg_match(self::YEAR, $li->textContent)));

        return match (true) {
            count($items) >= 3 && $dated * 2 >= count($items) => 'timeline',
            count($items) >= self::CHIPS_FROM => 'chips',
            default => 'text',
        };
    }

    /** A big number: the first figure of the text, lifted out of it ("(1,140,500)" with its brackets); the rest as written */
    protected function stat(string $html): ?array
    {
        if (! preg_match(self::NUMBER, $this->plain($html), $m)) {
            return null;
        }
        [$dom, $body] = $this->dom($html);
        $pattern = '/\(?\s*'.preg_quote($m[0], '/').'\s*\)?/u';
        foreach ((new DOMXPath($dom))->query('.//text()', $body) as $node) {
            if (preg_match($pattern, $node->nodeValue)) {
                $node->nodeValue = preg_replace('/ {2,}/', ' ', preg_replace($pattern, ' ', $node->nodeValue, 1));   // the space before a link stays
                break;
            }
        }
        $this->dropEmpty($dom, $body);
        $rest = $this->inner($dom, $body);

        return ['display' => 'stat', 'number' => $m[0], 'rest' => $this->blank($rest) ? null : new HtmlString($rest)];
    }

    /** Bars: "label · number" rows of a table or lines ("Officials: 351"); a "Total …" row apart, other rows as a line above */
    protected function bars(string $html): ?array
    {
        $pairs = $this->pairs($html);
        $total = null;
        $bars = $meta = [];
        foreach ($pairs as $pair) {
            match (true) {
                $pair['number'] === null => $meta[] = trim($pair['label'].' '.$pair['value']),
                (bool) preg_match('/^total\b/i', $pair['label']) => $total = $pair,
                default => $bars[] = $pair,
            };
        }
        if ($bars === []) {
            return null;
        }
        $max = max(array_column($bars, 'number')) ?: 1;

        return [
            'display' => 'bars',
            'meta' => $meta,
            'bars' => array_map(fn (array $b): array => ['label' => $b['label'], 'value' => $b['value'], 'percent' => round($b['number'] / $max * 100, 1)], $bars),
            'total' => $total ? ['label' => $total['label'], 'value' => $total['value']] : null,
        ];
    }

    /**
     * The first list's items as written (chips, cards, a timeline); what is around the list (a View Details
     * button) as extra. Timeline: grouped by the year in each item, newest first, items without one last.
     */
    protected function items(string $html, string $display): ?array
    {
        [$dom, $body] = $this->dom($html);
        $list = (new DOMXPath($dom))->query('(.//ul|.//ol)[1]', $body)->item(0);
        if (! $list) {
            return null;
        }
        $items = array_values(array_filter(array_map(fn (DOMElement $li): string => trim($this->inner($dom, $li)), $this->children($list, 'li')), fn (string $i): bool => ! $this->blank($i)));
        $list->parentNode->removeChild($list);
        $this->dropEmpty($dom, $body);
        $extra = $this->inner($dom, $body);
        $shaped = ['display' => $display, 'extra' => $this->blank($extra) ? null : new HtmlString($extra)];

        if ($display !== 'timeline') {
            return $shaped + ['items' => array_map(fn (string $i): HtmlString => new HtmlString($i), $items)];
        }
        $years = [];
        foreach ($items as $item) {
            $year = preg_match(self::YEAR, strip_tags($item), $m) ? $m[0] : '';
            $years[$year][] = new HtmlString($item);
        }
        uksort($years, fn ($a, $b): int => ($a === '') <=> ($b === '') ?: strcmp((string) $b, (string) $a));

        return $shaped + ['years' => array_map(fn ($year, array $list): array => ['year' => $year === '' ? null : (string) $year, 'items' => $list], array_keys($years), $years)];
    }

    /** @param array<string, mixed> $tile */
    protected function size(array $tile, string $html): string
    {
        $length = mb_strlen($this->plain($html));

        return match ($tile['display']) {
            'chips', 'timeline' => 'xl',
            'cards' => count($tile['items']) > 8 ? 'xl' : 'l',
            'bars' => count($tile['bars']) > 2 ? 'l' : 'm',
            'stat' => $tile['featured'] || mb_strlen(strip_tags((string) $tile['rest'])) > 90 ? 'l' : 's',
            default => $length > 220 ? 'l' : 'm',
        };
    }

    /**
     * Label / value pairs of a value: the rows of its table (none when a row has not two cells), else — unless
     * tableOnly — its lines "label: 351" / "label - 620", a line without a figure kept whole as a label.
     * number is the figure when the value is one (": 31" → 31), else null. Nothing of the text is left out.
     *
     * @return list<array{label: string, value: string, number: ?int}>
     */
    protected function pairs(string $html, bool $tableOnly = false): array
    {
        $pair = function (string $label, string $value): array {
            $value = trim(preg_replace('/^:\s*/', '', $value));

            return ['label' => rtrim($label, " :\u{00A0}"), 'value' => $value, 'number' => preg_match('/^\d[\d,]*\+?$/', $value) ? (int) str_replace([',', '+'], '', $value) : null];
        };
        [$dom, $body] = $this->dom($html);
        $rows = (new DOMXPath($dom))->query('.//tr', $body);
        if ($rows->length > 0) {
            $pairs = [];
            foreach ($rows as $tr) {
                $cells = array_values(array_filter($this->children($tr), fn (DOMElement $c): bool => in_array($c->nodeName, ['td', 'th'], true)));
                if (count($cells) !== 2) {
                    return [];
                }
                $pairs[] = $pair($this->squash($cells[0]->textContent), $this->squash($cells[1]->textContent));
            }

            return $pairs;
        }
        if ($tableOnly) {
            return [];
        }
        $lines = preg_split('/\n+/', html_entity_decode(strip_tags(preg_replace('#<br\s*/?>|</(div|p|li)>#i', "\n", $html)), ENT_QUOTES | ENT_HTML5));

        return collect($lines)->map(fn (string $line): string => $this->squash($line))->filter()
            ->map(fn (string $line): array => preg_match('/^(.+?)\s*[:\-–]\s*(\d[\d,]*\+?)$/u', $line, $m) ? $pair($m[1], $m[2]) : ['label' => $line, 'value' => '', 'number' => null])
            ->values()->all();
    }

    /** @return array{0: DOMDocument, 1: DOMElement} */
    protected function dom(string $html): array
    {
        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);

        return [$dom, $dom->getElementsByTagName('body')->item(0)];
    }

    protected function inner(DOMDocument $dom, DOMNode $node): string
    {
        return trim(implode('', array_map(fn (DOMNode $child): string => $dom->saveHTML($child), iterator_to_array($node->childNodes))));
    }

    /** @return list<DOMElement> */
    protected function children(DOMNode $node, ?string $name = null): array
    {
        return array_values(array_filter(iterator_to_array($node->childNodes), fn (DOMNode $c): bool => $c instanceof DOMElement && ($name === null || $c->nodeName === $name)));
    }

    /** Elements left without text, link or picture (the list item that held only the number, an empty paragraph) */
    protected function dropEmpty(DOMDocument $dom, DOMElement $body): void
    {
        foreach (array_reverse(iterator_to_array((new DOMXPath($dom))->query('.//li|.//ul|.//ol|.//p|.//div|.//strong', $body))) as $element) {
            if ($this->squash($element->textContent) === '' && $element->getElementsByTagName('a')->length === 0 && $element->getElementsByTagName('img')->length === 0) {
                $element->parentNode?->removeChild($element);
            }
        }
    }

    protected function blank(string $html): bool
    {
        return $this->squash(strip_tags($html)) === '' && ! preg_match('/<(a|img)\b/i', $html);
    }

    protected function plain(string $html): string
    {
        return $this->squash(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    protected function squash(string $text): string
    {
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text));
    }

    /** @param array<string, int> $ids */
    protected function unique(string $id, array &$ids): string
    {
        $ids[$id] = ($ids[$id] ?? 0) + 1;

        return $ids[$id] > 1 ? $id.'-'.$ids[$id] : $id;
    }
}
