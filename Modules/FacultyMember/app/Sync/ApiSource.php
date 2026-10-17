<?php

namespace Modules\FacultyMember\Sync;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Teachers from the faculty site's API (GET, optional bearer token). Follows Laravel-style
 * pagination (`next_page_url` or `links.next`) so a paged API comes in whole. When the real
 * API's field names differ, only RecordMapper needs to learn them.
 */
class ApiSource implements FacultyMemberSource
{
    public const MAX_PAGES = 200;

    public function __construct(protected string $url, protected ?string $token = null) {}

    public function records(): array
    {
        $records = [];
        $url = $this->url;

        for ($page = 0; $url && $page < self::MAX_PAGES; $page++) {
            try {
                $response = Http::acceptJson()->timeout(60)->retry(2, 500, throw: false)
                    ->when(filled($this->token), fn ($http) => $http->withToken((string) $this->token))
                    ->get($url);
            } catch (ConnectionException $e) {
                throw new SourceUnavailable(__('facultymember::facultymember.sync.no_api', ['why' => $e->getMessage()]));
            }
            if (! $response->successful()) {
                throw new SourceUnavailable(__('facultymember::facultymember.sync.no_api', ['why' => 'HTTP '.$response->status()]));
            }

            $body = $response->json();
            array_push($records, ...RecordList::from($body, allowEmpty: $page > 0));
            $url = is_array($body) ? ($body['next_page_url'] ?? $body['links']['next'] ?? null) : null;
        }

        return $records;
    }

    public function describe(): string
    {
        return 'API '.(parse_url($this->url, PHP_URL_HOST) ?: $this->url);
    }
}
