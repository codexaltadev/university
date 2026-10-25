<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DIU Faculty Management System (FMS) API — faculties, departments, teachers and publications
 * (https://demofaculty.daffodilvarsity.edu.bd/api/v1). Server side only: the token never reaches
 * a page. The limit is 120 requests a minute for the whole site, so every GET is cached, keyed by
 * path + query ("fms:…"); a fresh copy for its time, and a last good copy kept to answer a 429
 * (or the API being down) with. A failure gives null, is logged, and never breaks the page.
 * CV and vCard files are fetched (not cached) for a Laravel route to stream.
 */
class FmsApiClient
{
    /** Faculties, departments, lookups */
    public const TTL_STRUCTURE = 86400;

    /** Publication search */
    public const TTL_PUBLICATIONS = 1800;

    /** Every key this client wrote, for fms:clear-cache (the database cache store has no tags) */
    public const INDEX = 'fms:index';

    /** What a profile can include */
    public const INCLUDES = ['education', 'experience', 'training', 'awards', 'memberships', 'teaching_areas', 'research_interests', 'areas_of_expertise', 'skills', 'research', 'social_links'];

    public function __construct(protected ?string $url, protected ?string $token, protected int $ttl = 3600) {}

    /** @return array{data: list<array>}|null */
    public function faculties(): ?array
    {
        return $this->get('/faculties', [], self::TTL_STRUCTURE);
    }

    /** @param  string|int  $faculty  short name (FBE) or id */
    public function faculty(string|int $faculty): ?array
    {
        return $this->get($this->path('faculties', $faculty), [], self::TTL_STRUCTURE);
    }

    public function departments(string|int $faculty): ?array
    {
        return $this->get($this->path('faculties', $faculty, 'departments'), [], self::TTL_STRUCTURE);
    }

    /** @param  string|int  $department  code (IE) or id */
    public function department(string|int $faculty, string|int $department): ?array
    {
        return $this->get($this->path('faculties', $faculty, 'departments', $department), [], self::TTL_STRUCTURE);
    }

    /** @param  array{q?: string, designation?: string|int, per_page?: int, page?: int}  $query */
    public function departmentTeachers(string|int $faculty, string|int $department, array $query = []): ?array
    {
        return $this->get($this->path('faculties', $faculty, 'departments', $department, 'teachers'), $this->only($query, ['q', 'designation', 'per_page', 'page']));
    }

    public function allDepartments(): ?array
    {
        return $this->get('/departments', [], self::TTL_STRUCTURE);
    }

    /** @param  array{q?: string, designation?: string|int, faculty?: string|int, department?: string|int, per_page?: int, page?: int}  $query */
    public function teachers(array $query = []): ?array
    {
        return $this->get('/teachers', $this->only($query, ['q', 'designation', 'faculty', 'department', 'per_page', 'page']));
    }

    /** designations, employment_statuses, publication_types, publication_quartiles (id + name) */
    public function lookups(): ?array
    {
        return $this->get('/lookups', [], self::TTL_STRUCTURE);
    }

    /** @param  array{q?: string, year?: int, from?: string, to?: string, type?: string|int, faculty?: string|int, department?: string|int, per_page?: int, page?: int}  $query */
    public function publications(array $query = []): ?array
    {
        return $this->get('/publications', $this->only($query, ['q', 'year', 'from', 'to', 'type', 'faculty', 'department', 'per_page', 'page']), self::TTL_PUBLICATIONS);
    }

    /**
     * A teacher's profile (FBE / IE / muntachir).
     *
     * @param  list<string>  $include  parts of self::INCLUDES; empty = all
     */
    public function teacher(string|int $faculty, string|int $department, string $teacher, array $include = []): ?array
    {
        $include = array_values(array_intersect($include, self::INCLUDES));

        return $this->get($this->path($faculty, $department, $teacher), $include ? ['include' => implode(',', $include)] : []);
    }

    /** @param  array{per_page?: int, page?: int}  $query */
    public function teacherPublications(string|int $faculty, string|int $department, string $teacher, array $query = []): ?array
    {
        return $this->get($this->path($faculty, $department, $teacher, 'publications'), $this->only($query, ['per_page', 'page']));
    }

    public function publication(string|int $faculty, string|int $department, string $teacher, string|int $publication): ?array
    {
        return $this->get($this->path($faculty, $department, $teacher, 'publications', $publication), [], self::TTL_PUBLICATIONS);
    }

    /** The teacher's CV (a file) — for a route to stream; null when there is none or the API fails */
    public function cv(string|int $faculty, string|int $department, string $teacher): ?Response
    {
        return $this->file($this->path($faculty, $department, $teacher, 'cv'));
    }

    /** The teacher's vCard (a file) — for a route to stream */
    public function vcard(string|int $faculty, string|int $department, string $teacher): ?Response
    {
        return $this->file($this->path($faculty, $department, $teacher, 'vcard'));
    }

    public function configured(): bool
    {
        return filled($this->url) && filled($this->token);
    }

    /** Forget every cached answer (fms:clear-cache); the last good copies too */
    public function clearCache(): int
    {
        $keys = (array) Cache::get(self::INDEX, []);
        foreach ($keys as $key) {
            Cache::forget($key);
            Cache::forget($key.':last');
        }
        Cache::forget(self::INDEX);

        return count($keys);
    }

    /**
     * A cached GET: the fresh copy, else the API (kept for $ttl and as the last good copy), else on a
     * 429 / 5xx / no connection the last good copy, else null.
     */
    protected function get(string $path, array $query = [], ?int $ttl = null): ?array
    {
        ksort($query);
        $key = 'fms:'.md5($path.'?'.http_build_query($query));
        if (is_array($cached = Cache::get($key))) {
            return $cached;
        }
        if (! $this->configured()) {
            Log::warning('FMS API is not configured (FMS_API_URL / FMS_API_TOKEN).');

            return null;
        }

        try {
            $response = $this->request()->get($this->url($path), $query);
        } catch (ConnectionException $e) {
            Log::warning('FMS API unreachable: '.$path, ['error' => $e->getMessage()]);

            return $this->last($key);
        } catch (Throwable $e) {
            report($e);

            return $this->last($key);
        }

        if ($response->successful() && is_array($data = $response->json())) {
            Cache::put($key, $data, $ttl ?? $this->ttl);
            Cache::forever($key.':last', $data);
            $this->remember($key);

            return $data;
        }

        $status = $response->status();
        $context = ['path' => $path, 'query' => $query, 'status' => $status, 'message' => $response->json('message')];
        match (true) {
            $status === 401 => Log::error('FMS API refused the token (401): check FMS_API_TOKEN.', $context),
            $status === 404 => Log::info('FMS API: not found.', $context),
            default => Log::warning('FMS API request failed.', $context),
        };

        // Rate limit or the API down: the last good answer, if any; a 4xx (404, 422 …) is an answer
        return $status === 429 || $status >= 500 ? $this->last($key) : null;
    }

    protected function file(string $path): ?Response
    {
        if (! $this->configured()) {
            return null;
        }
        try {
            $response = $this->request()->accept('*/*')->timeout(20)->get($this->url($path));
        } catch (Throwable $e) {
            Log::warning('FMS API file failed: '.$path, ['error' => $e->getMessage()]);

            return null;
        }
        if (! $response->successful()) {
            Log::log($response->status() === 401 ? 'error' : 'warning', 'FMS API file request failed.', ['path' => $path, 'status' => $response->status()]);

            return null;
        }

        return $response;
    }

    protected function request(): PendingRequest
    {
        // Retry twice on no connection or a server error only — not on 4xx (429 has its own answer)
        return Http::withToken((string) $this->token)->acceptJson()->timeout(10)
            ->retry(2, 200, fn (Throwable $e): bool => $e instanceof ConnectionException || ($e->response?->serverError() ?? false), throw: false);
    }

    protected function url(string $path): string
    {
        return rtrim((string) $this->url, '/').$path;
    }

    /** "/a/b/c" from segments, each made safe for a path */
    protected function path(string|int ...$segments): string
    {
        return '/'.implode('/', array_map(fn (string|int $s): string => rawurlencode((string) $s), $segments));
    }

    /** The allowed, filled query parameters; per_page kept within 1–100 */
    protected function only(array $query, array $allowed): array
    {
        $query = array_filter(array_intersect_key($query, array_flip($allowed)), fn ($v): bool => $v !== null && $v !== '');
        if (isset($query['per_page'])) {
            $query['per_page'] = max(1, min(100, (int) $query['per_page']));
        }

        return $query;
    }

    protected function last(string $key): ?array
    {
        $last = Cache::get($key.':last');

        return is_array($last) ? $last : null;
    }

    protected function remember(string $key): void
    {
        $keys = (array) Cache::get(self::INDEX, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::INDEX, $keys);
        }
    }
}
