<?php

namespace Legacy\Migration\Support;

use Illuminate\Support\Str;

/** Cleaning of legacy values (Data Migration Plan §4 "Normalizer"). */
class Normalizer
{
    /** Where the old system's files live (Plan §3); S03 imports them into the Media Library later. */
    public const FILE_BASE = 'https://webbackend.daffodilvarsity.edu.bd/';

    /** Trimmed text, or null when empty. */
    public static function text(?string $value): ?string
    {
        $value = $value === null ? null : trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5));

        return $value === '' ? null : $value;
    }

    /** Rich text, or null when it holds no visible text. */
    public static function html(?string $value): ?string
    {
        if ($value === null || trim(strip_tags(str_replace('&nbsp;', ' ', $value))) === '') {
            return null;
        }

        return trim($value);
    }

    /** {"en": "…"} for a translatable column; legacy content is English. */
    public static function en(?string $value): ?array
    {
        return $value === null ? null : ['en' => $value];
    }

    public static function shortName(?string $value): ?string
    {
        $value = static::text($value);

        return $value === null ? null : Str::upper($value);
    }

    public static function url(?string $value): ?string
    {
        $value = static::text($value);

        return $value !== null && filter_var($value, FILTER_VALIDATE_URL) ? $value : null;
    }

    /** A stored file path as a public link on the old file server. */
    /** Paths of the old site's own files inside its texts (DIU RichHtmlContent: these go to webbackend) */
    public const FILE_PATHS = ['/photos', '/images', '/storage', '/brochure', '/labGallery', '/admin'];

    /** A legacy flipbook's folder on webbackend ("academic/academic-handbook") → the original PDF its viewer reads (PDF Flip: source="PDF.pdf") */
    public static function flipbookPdf(?string $folder): ?string
    {
        $folder = static::filePath($folder);

        return $folder === null ? null : rtrim($folder, '/').'/PDF.pdf';
    }

    /** DIU's article address → the page's here: /article/{slug} → /{slug} (S15A: articles are general pages); others unchanged */
    public static function pagePath(?string $url): ?string
    {
        return $url === null ? null : (preg_replace('~^(?:https?://(?:www\.)?daffodilvarsity\.edu\.bd)?/article/([^/?#]+)~i', '/$1', $url) ?? $url);
    }

    /**
     * Rich text of the old site with its relative pictures (and links to its files) pointing at
     * webbackend, as DIU's frontend shows them; page links /article/{slug} become /{slug} (pagePath).
     * S03 later swaps the files for Media Library addresses.
     */
    public static function legacyHtml(?string $value): ?string
    {
        $html = static::html($value);
        if ($html === null) {
            return null;
        }

        $html = preg_replace_callback('~(<img\b[^>]*?\ssrc\s*=\s*)(["\'])(?!https?:|//|data:)([^"\']+)\2~i',
            fn (array $m): string => $m[1].$m[2].self::FILE_BASE.ltrim($m[3], '/').$m[2], $html);

        $html = preg_replace_callback('~(<a\b[^>]*?\shref\s*=\s*)(["\'])((?:https?://(?:www\.)?daffodilvarsity\.edu\.bd)?/article/[^"\']*)\2~i',
            fn (array $m): string => $m[1].$m[2].static::pagePath($m[3]).$m[2], $html);

        return preg_replace_callback('~(<a\b[^>]*?\shref\s*=\s*)(["\'])(/[^"\']*)\2~i',
            fn (array $m): string => collect(self::FILE_PATHS)->contains(fn (string $p): bool => str_starts_with($m[3], $p.'/'))
                ? $m[1].$m[2].self::FILE_BASE.ltrim($m[3], '/').$m[2] : $m[0], $html);
    }

    /**
     * A legacy file's path as stored on webbackend ("uploads/a.jpg"), from a column value that may be
     * the path with or without "/" or the full address; null for empty values and other sites' links.
     */
    public static function filePath(?string $value): ?string
    {
        $value = static::text($value);
        if ($value === null) {
            return null;
        }
        if (str_starts_with($value, 'http')) {
            if (! str_starts_with($value, self::FILE_BASE) && ! str_starts_with($value, str_replace('https:', 'http:', self::FILE_BASE))) {
                return null;
            }
            $value = (string) parse_url($value, PHP_URL_PATH);
        }
        $value = ltrim(rawurldecode($value), '/');

        return $value === '' ? null : $value;
    }

    public static function fileUrl(?string $path): ?string
    {
        $path = static::text($path);

        return $path === null ? null : (str_starts_with($path, 'http') ? $path : self::FILE_BASE.ltrim($path, '/'));
    }

    /**
     * Credits, years and semesters from the free text "program_credit_duration"
     * (DB-03 §15: parse + manual check), e.g. "Credit-154 … Duration -04 Years",
     * "a total of 194 credit hours … five (5) academic years", "12 semesters".
     *
     * @return array{credits: ?float, years: ?float, terms: ?int}
     */
    public static function creditsAndDuration(?string $html): array
    {
        $text = preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5)));

        $credits = preg_match('/(\d{2,3}(?:\.\d)?)\s*(?:credit|cr\.?\b)/i', $text, $m) || preg_match('/credits?\s*(?:hours?)?\s*[-:]?\s*(\d{2,3}(?:\.\d)?)/i', $text, $m)
            ? (float) $m[1] : null;
        // "4 Years", "04 Years", "4 (four) academic years", "five (5) academic years"
        $years = preg_match('/(\d(?:\.\d)?)\)?\s*(?:\([a-z]+\)\s*)?(?:academic\s+)?years?/i', $text, $m) ? (float) $m[1] : null;
        $terms = preg_match('/(\d{1,2})\s*(?:\([a-z]+\)\s*)?(?:semesters|trimesters)/i', $text, $m) ? (int) $m[1] : null;

        return ['credits' => $credits, 'years' => $years, 'terms' => $terms];
    }

    /**
     * An old page that is one two-column table (DIU's At a Glance: "Faculties :" | the list) as the
     * key facts block's data (UI/UX Part B §5.8): label = the first cell's text, value = the second
     * cell's HTML as written, without the editor's sizes (style, width, height); what follows the
     * table is the note. Null when the page is anything else (also a two-column table whose labels mostly
     * have no colon: a calendar, a committee) — then it stays an HTML block.
     *
     * @return array{highlights: list<array>, rows: list<array{label: string, value: string}>, show_nav: bool, note: ?string}|null
     */
    public static function factsTable(?string $html): ?array
    {
        if (static::html($html) === null) {
            return null;
        }

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $nodes = fn (\DOMNode $node): array => array_values(array_filter(iterator_to_array($node->childNodes),
            fn (\DOMNode $child): bool => $child instanceof \DOMElement || trim(str_replace("\u{00A0}", ' ', $child->textContent)) !== ''));

        $top = $nodes($dom->getElementsByTagName('body')->item(0));
        $first = $top[0] ?? null;
        $table = $first?->nodeName === 'div' && count($nodes($first)) === 1 ? $nodes($first)[0] : $first;   // Bootstrap's .table-responsive around it
        if ($table?->nodeName !== 'table') {
            return null;
        }

        $rows = [];
        foreach ($nodes($table) as $part) {
            foreach (in_array($part->nodeName, ['tbody', 'thead'], true) ? $nodes($part) : [$part] as $tr) {
                $cells = $tr->nodeName === 'tr' ? $nodes($tr) : [];
                if (count($cells) !== 2 || array_diff(array_map(fn ($c) => $c->nodeName, $cells), ['td', 'th']) !== []) {
                    return null;
                }
                foreach ((new \DOMXPath($dom))->query('.//*[@style or @width or @height]', $cells[1]) as $sized) {
                    $sized->removeAttribute('style');
                    $sized->removeAttribute('width');
                    $sized->removeAttribute('height');
                }
                $label = static::text(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $cells[0]->textContent)));
                $value = trim(implode('', array_map(fn (\DOMNode $n): string => $dom->saveHTML($n), iterator_to_array($cells[1]->childNodes))));
                if ($label !== null) {
                    $rows[] = ['label' => $label, 'value' => $value === '' ? '<p></p>' : $value];
                }
            }
        }
        // A fact sheet's labels end with a colon ("Faculties :"); a calendar, a list or a committee's table does not
        $labelled = count(array_filter($rows, fn (array $row): bool => str_ends_with($row['label'], ':')));
        if (count($rows) < 3 || $labelled < 0.8 * count($rows)) {
            return null;
        }

        $note = static::html(implode('', array_map(fn (\DOMNode $n): string => $dom->saveHTML($n), array_slice($top, 1))));

        return ['highlights' => [], 'rows' => $rows, 'show_nav' => true, 'note' => $note];   // S15A puts the rows into chapters
    }
}
