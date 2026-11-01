<?php

namespace Modules\Page\Blocks;

use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Platform\Pages\PageBlock;
use Throwable;

/**
 * Job listings from skill.jobs (DIU's /career): the university's openings read straight from the company's SkillJobs
 * address typed into the block — nothing kept here; the answer is remembered for a while so not every visit asks.
 */
final class JobsBlock extends PageBlock
{
    public const SITE = 'https://skill.jobs/jobs/';

    public const FILES = 'https://studio.skill.jobs';

    /** Seconds the list is remembered */
    public const CACHE = 1800;

    public static function name(): string
    {
        return 'jobs';
    }

    public static function label(): string
    {
        return __('page::page.blocks.jobs');
    }

    public static function icon(): string|BackedEnum|null
    {
        return Heroicon::OutlinedBriefcase;
    }

    public static function schema(): array
    {
        return [
            TextInput::make('heading')->label(__('page::page.fields.heading'))->maxLength(150),
            TextInput::make('source')->label(__('page::page.fields.jobs_source'))->required()->url()->maxLength(500)
                ->regex('#^https://studio\.skill\.jobs/#')->helperText(__('page::page.help.jobs_source')),
        ];
    }

    /**
     * The openings, newest first as SkillJobs gives them; null when SkillJobs could not be reached.
     *
     * @return list<array{title: string, company: ?string, logo: ?string, type: ?string, location: ?string, workplace: ?string, level: ?string, vacancies: ?int, deadline: ?string, expired: bool, url: string}>|null
     */
    public static function jobs(array $data): ?array
    {
        $source = (string) ($data['source'] ?? '');
        if (! str_starts_with($source, 'https://studio.skill.jobs/')) {
            return null;
        }

        $results = Cache::get($key = 'page.jobs.'.md5($source));
        if ($results === null) {
            try {
                $response = Http::timeout(8)->acceptJson()->get($source);
                $results = $response->successful() ? (array) $response->json('results', []) : null;
            } catch (Throwable) {
                $results = null;
            }
            if ($results === null) {
                return null;   // asked again on the next visit
            }
            Cache::put($key, $results, self::CACHE);
        }

        return collect($results)->filter(fn ($job): bool => is_array($job) && filled($job['title'] ?? null) && filled($job['slug'] ?? null))
            ->map(fn (array $job): array => [
                'title' => (string) $job['title'],
                'company' => $job['company_info']['name'] ?? null,
                'logo' => filled($logo = $job['company_info']['logo'] ?? null) ? self::FILES.'/'.ltrim((string) $logo, '/') : null,
                'type' => filled($job['type'] ?? null) ? (string) $job['type'] : null,
                'location' => filled($job['location'] ?? null) ? (string) $job['location'] : null,
                'workplace' => filled($job['workplace'] ?? null) ? (string) $job['workplace'] : null,
                'level' => filled($job['level'] ?? null) ? (string) $job['level'] : null,
                'vacancies' => is_numeric($job['no_of_vacancy'] ?? null) ? (int) $job['no_of_vacancy'] : null,
                'deadline' => filled($job['end_date'] ?? null) ? (string) $job['end_date'] : null,
                'expired' => (bool) ($job['isExpired'] ?? false),
                'url' => self::SITE.rawurlencode((string) $job['slug']),
            ])->values()->all();
    }
}
