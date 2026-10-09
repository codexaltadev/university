<?php

namespace Modules\Seo\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Seo\Models\NotFoundLog;
use Modules\Seo\Models\Redirect;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use RuntimeException;

/**
 * Redirects (C-12, FR-SEO-04/05/09): normalized lookup with a cache, saving
 * without chains or loops (A → B → A is refused; A → B when B → C already exists
 * becomes A → C; X → A becomes X → B), CSV import, internal targets.
 */
class Redirects
{
    public const VERSION_KEY = 'seo:redirects:version';

    public const MAX_HOPS = 10;

    /** Addresses never redirected or logged: the admin panel and assets. */
    public const RESERVED = ['/admin', '/livewire', '/filament', '/storage', '/themes', '/build', '/css', '/js', '/fonts', '/media', '/theme-preview'];

    /** "/Old-Page/?x=1" → "/old-page" */
    public static function normalize(?string $path): string
    {
        $path = (string) parse_url(trim((string) $path), PHP_URL_PATH);
        $path = '/'.trim((string) preg_replace('#/+#', '/', rawurldecode($path)), '/');

        return mb_strtolower($path);
    }

    public static function hash(string $normalized): string
    {
        return hash('sha256', $normalized);
    }

    public static function isReserved(string $path): bool
    {
        $path = static::normalize($path);

        foreach (self::RESERVED as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /** A path or a URL on this website → its normalized path; another website → null. */
    public static function internalPath(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return static::normalize($url);
        }

        $host = parse_url($url, PHP_URL_HOST);

        return $host && strcasecmp($host, (string) parse_url(url('/'), PHP_URL_HOST)) === 0 ? static::normalize($url) : null;
    }

    /** @return array{id: int, url: string, status: int}|null the active redirect for an address */
    public function find(string $path): ?array
    {
        $hash = static::hash(static::normalize($path));
        $version = Cache::get(self::VERSION_KEY, 0);

        // The row is cached; a record's address is asked each time, so a later slug change is followed (P-15)
        $row = Cache::remember("seo:redirect:{$version}:{$hash}", now()->addDay(), fn (): array|false => Redirect::query()
            ->where('source_hash', $hash)->where('is_active', true)
            ->first(['id', 'destination_url', 'target_type', 'target_id', 'status_code'])?->getAttributes() ?? false);

        if (! $row) {
            return null;
        }

        $url = (new Redirect)->forceFill($row)->destination();

        return $url ? ['id' => (int) $row['id'], 'url' => $url, 'status' => (int) $row['status_code']] : null;
    }

    public function hit(int $id): void
    {
        Redirect::query()->whereKey($id)->update(['hits' => DB::raw('hits + 1'), 'last_hit_at' => now()]);
    }

    /**
     * Create or change a redirect. Data: source_path, destination_url | target ("type:id"),
     * status_code, is_active, source.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws RuntimeException with the reason it cannot be saved
     */
    public function save(array $data, ?Redirect $redirect = null): Redirect
    {
        return DB::transaction(function () use ($data, $redirect): Redirect {
            $source = static::normalize($data['source_path'] ?? '');
            [$url, $target] = $this->finalDestination($source, $data['destination_url'] ?? null, $data['target'] ?? null, $redirect?->id);

            $redirect ??= new Redirect;
            $redirect->fill([
                'source_path' => $source,
                'destination_url' => $target ? null : $url,
                'target_type' => $target?->getMorphClass(),
                'target_id' => $target?->getKey(),
                'status_code' => (int) ($data['status_code'] ?? 301),
                'is_active' => (bool) ($data['is_active'] ?? true),
                'source' => $data['source'] ?? $redirect->source ?? 'manual',
            ])->save();

            // X → (this source) now goes straight to the new destination: no chains (FR-SEO-09)
            Redirect::query()->whereKeyNot($redirect->id)->whereNotNull('destination_url')->get()
                ->filter(fn (Redirect $other): bool => static::internalPath($other->destination_url) === $source)
                ->each(fn (Redirect $other) => $other->fill([
                    'destination_url' => $redirect->destination_url,
                    'target_type' => $redirect->target_type,
                    'target_id' => $redirect->target_id,
                ])->save());

            NotFoundLog::resolve($source);

            return $redirect;
        });
    }

    /**
     * Why a redirect cannot be saved, or null (form validation).
     *
     * @param  array<string, mixed>  $data
     */
    public function problem(array $data, ?int $ignoreId = null): ?string
    {
        $source = static::normalize($data['source_path'] ?? '');

        if ($source === '/' || static::isReserved($source)) {
            return __('seo::seo.errors.reserved');
        }

        $taken = Redirect::query()->where('source_hash', static::hash($source))
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists();

        if ($taken) {
            return __('seo::seo.errors.taken');
        }

        try {
            $this->finalDestination($source, $data['destination_url'] ?? null, $data['target'] ?? null, $ignoreId);
        } catch (RuntimeException $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * FR-SEO-05: CSV with the columns source, destination[, status]. A header row is
     * skipped; an existing source is updated.
     *
     * @return array{created: int, updated: int, skipped: list<string>}
     */
    public function import(string $file): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => []];
        $handle = fopen($file, 'r');

        for ($line = 1; ($row = fgetcsv($handle, escape: '\\')) !== false; $line++) {
            $row = array_map(fn ($cell) => trim((string) $cell, " \t\n\r\0\x0B\u{FEFF}"), $row);

            if ($line === 1 && in_array(strtolower($row[0] ?? ''), ['source', 'source_path', 'from', 'old'], true)) {
                continue;
            }

            if (count(array_filter($row)) === 0) {
                continue;
            }

            $data = [
                'source_path' => $row[0] ?? '',
                'destination_url' => $row[1] ?? '',
                'status_code' => in_array((int) ($row[2] ?? 301), Redirect::STATUS_CODES, true) ? (int) ($row[2] ?? 301) : 301,
                'source' => 'import',
            ];

            if (blank($data['destination_url'])) {
                $result['skipped'][] = __('seo::seo.import.line', ['line' => $line, 'reason' => __('seo::seo.errors.no_destination')]);

                continue;
            }

            $existing = Redirect::query()->where('source_hash', static::hash(static::normalize($data['source_path'])))->first();

            if ($problem = $this->problem($data, $existing?->id)) {
                $result['skipped'][] = __('seo::seo.import.line', ['line' => $line, 'reason' => $problem]);

                continue;
            }

            $this->save($data, $existing);
            $existing ? $result['updated']++ : $result['created']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * Records a redirect may point to: models that registered a sitemap and have url().
     *
     * @return array<string, string> "type:id" => "Type — title"
     */
    public function searchTargets(string $search, int $limit = 30): array
    {
        $results = [];

        foreach ($this->targetModels() as $model) {
            $title = $model::$titleAttribute ?? 'title';
            $query = $model::query()->withoutGlobalScope(UserScopeFilter::class)
                ->where($title, 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%')->limit($limit);

            foreach ($query->get() as $record) {
                $results[$this->targetKey($record)] = $this->targetLabel($record);
            }
        }

        return $results;
    }

    public function targetKey(Model $record): string
    {
        return $record->getMorphClass().':'.$record->getKey();
    }

    public function targetLabel(?Model $record): ?string
    {
        if ($record === null) {
            return null;
        }

        $title = $record->getAttribute($record::$titleAttribute ?? 'title');

        return Str::headline(Str::afterLast($record->getMorphClass(), '.')).' — '.$title.' ('.$record->url().')';
    }

    public function resolveTarget(?string $key): ?Model
    {
        if (blank($key) || ! str_contains($key, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $key, 2);
        $class = Relation::getMorphedModel($type);

        return $class && in_array($class, $this->targetModels(), true)
            ? $class::query()->withoutGlobalScope(UserScopeFilter::class)->find((int) $id)
            : null;
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /** @return list<class-string<BaseModel>> */
    protected function targetModels(): array
    {
        return collect(app(Registry::class)->all('sitemap'))->pluck('model')
            ->filter(fn (string $model): bool => method_exists($model, 'url'))->unique()->values()->all();
    }

    /**
     * Follows the destination through existing redirects to its end (FR-SEO-09).
     *
     * @return array{0: ?string, 1: ?Model} [url, target record]
     *
     * @throws RuntimeException loop, missing or unknown destination
     */
    protected function finalDestination(string $source, ?string $url, ?string $targetKey, ?int $ignoreId): array
    {
        $target = $this->resolveTarget($targetKey);

        if ($target) {
            if (static::normalize($target->url()) === $source) {
                throw new RuntimeException(__('seo::seo.errors.loop'));
            }

            return [null, $target];
        }

        if (blank($url)) {
            throw new RuntimeException(__('seo::seo.errors.no_destination'));
        }

        $url = trim($url);

        if (! str_starts_with($url, '/') && ! preg_match('#^https?://#i', $url)) {
            throw new RuntimeException(__('seo::seo.errors.bad_destination'));
        }

        for ($hops = 0; ($path = static::internalPath($url)) !== null; $hops++) {
            if ($path === $source || $hops >= self::MAX_HOPS) {
                throw new RuntimeException(__('seo::seo.errors.loop'));
            }

            $next = Redirect::query()->where('source_hash', static::hash($path))
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->first();

            if ($next === null) {
                break;
            }

            if ($next->target_type) {
                return [null, $next->target];
            }

            $url = (string) $next->destination_url;
        }

        return [$url, null];
    }
}
