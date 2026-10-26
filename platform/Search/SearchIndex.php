<?php

namespace Platform\Search;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Platform\Concerns\IsSearchable;
use Platform\Localization\Languages;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use Platform\Seo\Seo;
use Platform\Support\PublishDate;

/**
 * Website search (C-17, SAD Part B §3): one index row per visible record and
 * language (FR-SRCH-03/04), kept current by IndexSearchDocument (FR-SRCH-09).
 * MySQL FULLTEXT in boolean mode with prefix matching; words shorter than the
 * FULLTEXT minimum, and Bangla words, are matched with LIKE as well. A word may have
 * synonyms (settings('search.synonyms'): "cse = computer science and engineering"):
 * a record matching any of them matches the word. Titles that start with the query
 * come first.
 */
class SearchIndex
{
    public const MAX_QUERY = 100;

    public const EXCERPT = 220;

    /** @var array{0: string, 1: array<string, list<string>>}|null the synonyms text and its map */
    protected ?array $synonyms = null;

    public function __construct(protected Registry $registry, protected Languages $languages) {}

    /**
     * The synonyms from Settings, one rule a line: "cse, c.s.e = computer science and engineering, computer science".
     *
     * @return array<string, list<string>> word (lower case) => its synonyms
     */
    public function synonyms(): array
    {
        $text = (string) settings('search.synonyms');
        if ($this->synonyms !== null && $this->synonyms[0] === $text) {
            return $this->synonyms[1];
        }

        $map = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            if (! str_contains($line, '=')) {
                continue;
            }
            [$words, $means] = array_map('trim', explode('=', $line, 2));
            $means = array_values(array_filter(array_map(fn (string $m): string => Str::lower(Str::squish($m)), explode(',', $means))));
            foreach (array_filter(array_map(fn (string $w): string => Str::lower(trim($w)), explode(',', $words))) as $word) {
                $map[$word] = array_values(array_unique([...($map[$word] ?? []), ...array_diff($means, [$word])]));
            }
        }

        $this->synonyms = [$text, $map];

        return $map;
    }

    /** @return list<string> the term and its synonyms */
    protected function alternatives(string $term): array
    {
        return [$term, ...($this->synonyms()[Str::lower($term)] ?? [])];
    }

    /** @return array<class-string<Model>, array{module: string, label: string, weight: int}> */
    public function registered(): array
    {
        $models = [];

        foreach ($this->registry->all('searchable') as $entry) {
            $models[$entry['model']] = [
                'module' => $entry['module'],
                'label' => $entry['label'] ? __($entry['label']) : Str::headline($entry['module']),
                'weight' => (int) ($entry['weight'] ?? 5),
            ];
        }

        return $models;
    }

    /** @return array<string, string> module slug => group label (a module with several searchable models: its first, the main one) */
    public function modules(): array
    {
        $labels = [];
        foreach ($this->registered() as $info) {
            $labels[$info['module']] ??= $info['label'];
        }

        return $labels;
    }

    /** Index one record again (or remove it when it is gone, hidden or no longer registered). */
    public function refresh(string $type, int|string $id): void
    {
        $class = Relation::getMorphedModel($type) ?? $type;
        $info = $this->registered()[$class] ?? null;
        $record = $info && class_exists($class) ? $this->query($class)->find($id) : null;

        if ($record === null || ! $this->visible($record)) {
            $this->remove($type, $id);

            return;
        }

        $this->write($record, $info);
    }

    public function remove(string $type, int|string $id): void
    {
        SearchDocument::query()->where('searchable_type', $type)->where('searchable_id', $id)->delete();
    }

    /**
     * Index every visible record of the registered models again (uwms:search:rebuild).
     *
     * @return int records indexed
     */
    public function rebuild(?string $module = null): int
    {
        $count = 0;

        foreach ($this->registered() as $class => $info) {
            if ($module !== null && $info['module'] !== $module) {
                continue;
            }

            SearchDocument::query()->where('searchable_type', (new $class)->getMorphClass())->delete();

            $query = $this->query($class);
            if (is_subclass_of($class, BaseModel::class)) {
                $query->frontendVisible();
            }

            $query->lazyById(200)->each(function (Model $record) use ($info, &$count): void {
                $this->write($record, $info);
                $count++;
            });
        }

        return $count;
    }

    /** Module deactivated or uninstalled: its records leave the website search. */
    public function forgetModule(string $slug): void
    {
        SearchDocument::query()->where('module_slug', $slug)->delete();
    }

    /** @param list<string> $types */
    public function forgetTypes(array $types): void
    {
        SearchDocument::query()->whereIn('searchable_type', $types)->delete();
    }

    /**
     * FR-SRCH-02/04/06: results in the current language, grouped by module, with
     * highlighted title and excerpt.
     *
     * @return array{query: string, total: int, groups: array<string, array{label: string, count: int}>, items: LengthAwarePaginator}
     */
    public function search(string $query, ?string $module = null, ?string $ownerKey = null, int $perPage = 10, int $page = 1, ?string $locale = null): array
    {
        $query = Str::of($query)->squish()->limit(self::MAX_QUERY, '')->toString();
        $locale ??= app()->getLocale();
        $terms = $this->terms($query);
        $labels = $this->modules();

        if ($terms === []) {
            return ['query' => $query, 'total' => 0, 'groups' => [], 'items' => new LengthAwarePaginator([], 0, $perPage, $page)];
        }

        $marks = collect($terms)->flatMap(fn (string $t): array => $this->alternatives($t))->unique()->values()->all();   // what to highlight

        $base = fn (): Builder => $this->matching(SearchDocument::query(), $terms)
            ->where('locale', $locale)
            ->whereIn('module_slug', array_keys($labels))
            ->when($ownerKey, fn (Builder $q) => $q->where('owner_key', $ownerKey))
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));

        $groups = $base()->select('module_slug', DB::raw('count(*) as aggregate'))->groupBy('module_slug')->pluck('aggregate', 'module_slug')
            ->sortKeysUsing(fn (string $a, string $b): int => array_search($a, array_keys($labels), true) <=> array_search($b, array_keys($labels), true))
            ->map(fn ($count, string $slug): array => ['label' => $labels[$slug] ?? Str::headline($slug), 'count' => (int) $count])
            ->all();

        $items = $base()
            ->when($module, fn (Builder $q) => $q->where('module_slug', $module))
            ->select('*')
            // the text's relevance, damped (a long text that repeats a word must not beat a page about it), then the title:
            // the whole query in it, starting it, and each word or synonym in it
            ->selectRaw('(LN(1 + MATCH(title, body) AGAINST (? IN BOOLEAN MODE)) * weight * 4 + IF(title LIKE ?, 10, 0) + IF(title LIKE ?, 15, 0)'
                .str_repeat(' + IF(title LIKE ?, 60, 0)', count($titleMarks = array_slice($marks, 0, 8))).') as score',
                [$this->booleanQuery($terms), '%'.$this->escapeLike($query).'%', $this->escapeLike($query).'%', ...array_map(fn (string $m): string => '%'.$this->escapeLike($m).'%', $titleMarks)])
            ->orderByDesc('score')->orderByDesc('published_at')->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (SearchDocument $doc): array => [
                'title' => $this->highlight(e($doc->title), $marks),
                'excerpt' => $this->highlight(e($this->excerpt((string) $doc->body, $marks)), $marks),
                'url' => $doc->url,
                'module' => $doc->module_slug,
                'label' => $labels[$doc->module_slug] ?? Str::headline($doc->module_slug),
                'published_at' => $doc->published_at,
            ]);

        return ['query' => $query, 'total' => array_sum(array_column($groups, 'count')), 'groups' => $groups, 'items' => $items];
    }

    /**
     * The modules' own pages whose name holds every word of the query, or a synonym of it ("tuition fees" → Tuition Fees,
     * "cse" with its synonym → …): the page a visitor most likely wants, shown above the results. These pages (Tuition
     * Fees, Waiver Calculator, Transport …) are not records, so the index does not hold them.
     *
     * @return list<array{label: string, url: string}>
     */
    public function shortcuts(string $query, int $limit = 3): array
    {
        $terms = $this->terms(Str::of($query)->squish()->limit(self::MAX_QUERY, '')->toString());
        if ($terms === []) {
            return [];
        }

        return collect($this->registry->all('websitePage'))
            ->map(fn (array $page): array => ['label' => (string) __($page['label']), 'url' => url(Seo::localized($page['path'], app()->getLocale()))])
            ->filter(fn (array $page): bool => collect($terms)->every(fn (string $term): bool => collect($this->alternatives($term))
                ->contains(fn (string $word): bool => mb_stripos($page['label'], $word) !== false)))
            ->unique('url')->take($limit)->values()->all();
    }

    /** @param array{module: string, label: string, weight: int} $info */
    protected function write(Model $record, array $info): void
    {
        foreach ($this->languages->activeCodes() as $locale) {
            $document = method_exists($record, 'toSearchDocument') ? $record->toSearchDocument($locale) : null;
            $url = $document['url'] ?? (method_exists($record, 'url') ? $record->url($locale) : null);
            $keys = ['searchable_type' => $record->getMorphClass(), 'searchable_id' => $record->getKey(), 'locale' => $locale];

            if ($document === null || blank($document['title'] ?? null) || blank($url)) {
                SearchDocument::query()->where($keys)->delete();

                continue;
            }

            SearchDocument::query()->updateOrCreate($keys, [
                'module_slug' => $info['module'],
                'owner_key' => method_exists($record, 'searchOwnerKey') ? $record->searchOwnerKey() : null,
                'title' => Str::limit(IsSearchable::plainText($document['title']), 490, ''),
                'body' => Str::limit(IsSearchable::plainText($document['body'] ?? ''), 60000, ''),
                'url' => Seo::localized($url, $locale),
                'weight' => $info['weight'],
                'published_at' => ($column = PublishDate::column($record)) ? $record->getAttribute($column) : null,   // published_at or active_at
            ]);
        }
    }

    protected function query(string $class): Builder
    {
        $query = $class::query()->withoutGlobalScope(UserScopeFilter::class);

        return $query;
    }

    protected function visible(Model $record): bool
    {
        return ! method_exists($record, 'isFrontendVisible') || $record->isFrontendVisible();
    }

    /** @return list<string> */
    protected function terms(string $query): array
    {
        return collect(preg_split('/\s+/u', preg_replace('/[+\-<>()~*"@%_\\\\]+/u', ' ', $query)))
            ->map(fn (string $t): string => trim($t))
            ->filter(fn (string $t): bool => mb_strlen($t) >= 1)
            ->unique()->take(8)->values()->all();
    }

    /**
     * Every term must match — itself or one of its synonyms: FULLTEXT for long Latin words,
     * LIKE for short words, phrases and scripts the FULLTEXT parser splits (Bangla vowel
     * signs, FR-SRCH-04).
     *
     * @param  list<string>  $terms
     */
    protected function matching(Builder $query, array $terms): Builder
    {
        foreach ($terms as $term) {
            $query->where(function (Builder $q) use ($term): void {
                foreach ($this->alternatives($term) as $word) {
                    $like = '%'.$this->escapeLike($word).'%';
                    $q->orWhere('title', 'like', $like)->orWhere('body', 'like', $like);

                    if ($this->fullTextFriendly($word)) {
                        $q->orWhereRaw('MATCH(title, body) AGAINST (? IN BOOLEAN MODE)', [$word.'*']);
                    }
                }
            });
        }

        return $query;
    }

    /** @param list<string> $terms the words (prefix) and their synonyms (a phrase in quotes) for the score */
    protected function booleanQuery(array $terms): string
    {
        $parts = [];
        foreach ($terms as $term) {
            foreach ($this->alternatives($term) as $i => $word) {
                if ($i === 0 && $this->fullTextFriendly($word)) {
                    $parts[] = $word.'*';
                } elseif ($i > 0 && preg_match('/^[\p{Latin}\p{N} ]+$/u', $word) && mb_strlen($word) >= 3) {
                    $parts[] = str_contains($word, ' ') ? '"'.$word.'"' : $word.'*';
                }
            }
        }

        return $parts === [] ? '""' : implode(' ', array_unique($parts));
    }

    protected function fullTextFriendly(string $term): bool
    {
        return mb_strlen($term) >= 3 && (bool) preg_match('/^[\p{Latin}\p{N}]+$/u', $term);
    }

    protected function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /** @param list<string> $terms */
    protected function excerpt(string $body, array $terms): string
    {
        $position = collect($terms)->map(fn (string $t) => mb_stripos($body, $t))->filter(fn ($p) => $p !== false)->min() ?? 0;
        $start = max(0, $position - 60);
        $excerpt = mb_substr($body, $start, self::EXCERPT);

        return ($start > 0 ? '… ' : '').$excerpt.(mb_strlen($body) > $start + self::EXCERPT ? ' …' : '');
    }

    /** @param list<string> $terms */
    protected function highlight(string $escaped, array $terms): HtmlString
    {
        $pattern = collect($terms)->map(fn (string $t): string => preg_quote(e($t), '/'))->implode('|');

        return new HtmlString($pattern === '' ? $escaped : (string) preg_replace("/({$pattern})/iu", '<mark>$1</mark>', $escaped));
    }
}
