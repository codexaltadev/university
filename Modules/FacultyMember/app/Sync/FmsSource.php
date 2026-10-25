<?php

namespace Modules\FacultyMember\Sync;

use App\Services\FmsApiClient;
use Illuminate\Support\Str;

/**
 * Teachers from DIU's Faculty Management System (FMS) API through App\Services\FmsApiClient (its
 * address and token in .env, its answers cached): /teachers page by page, 100 at a time, each
 * teacher turned into this module's record format: name, designation, department, photo,
 * contacts, bio and the teacher's FMS address. The profile itself is the faculty website's
 * (owner 2026-10-25): this site links to it, it has no profile page of its own. The sample JSON
 * file is in the same format (JsonFileSource reads it through record()).
 */
class FmsSource implements FacultyMemberSource
{
    public const PER_PAGE = 100;

    public const MAX_PAGES = 200;

    /** FMS employment statuses that take a teacher off the website (others count as active) */
    public const GONE = ['inactive', 'resign', 'retire', 'left', 'terminat', 'dismiss', 'deceased', 'expired', 'discontinu'];

    public function __construct(protected FmsApiClient $fms) {}

    public function records(): array
    {
        if (! $this->fms->configured()) {
            throw new SourceUnavailable(__('facultymember::facultymember.sync.no_fms'));
        }

        $records = [];
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $body = $this->fms->teachers(['per_page' => self::PER_PAGE, 'page' => $page]);
            if ($body === null) {
                throw new SourceUnavailable(__('facultymember::facultymember.sync.no_api', ['why' => 'FMS API']));
            }
            foreach (RecordList::from($body, allowEmpty: $page > 1) as $teacher) {
                $records[] = self::record($teacher);
            }
            if ($page >= (int) ($body['meta']['last_page'] ?? 1)) {
                break;
            }
        }

        return $records;
    }

    public function describe(): string
    {
        return 'FMS API '.(parse_url((string) config('services.fms.url'), PHP_URL_HOST) ?: '');
    }

    /** Is this raw record in the FMS format (a department object, full_name)? */
    public static function looksLikeFms(array $raw): bool
    {
        return is_array($raw['department'] ?? null) || array_key_exists('full_name', $raw);
    }

    /** One FMS teacher → this module's record (RecordMapper reads it) */
    public static function record(array $t): array
    {
        $department = (array) ($t['department'] ?? []);
        $faculty = (array) ($department['faculty'] ?? []);
        $status = Str::lower((string) ($t['employment_status'] ?? ''));
        $path = array_filter([$faculty['short_name'] ?? $faculty['code'] ?? null, $department['code'] ?? null, $t['slug'] ?? null], 'filled');

        return array_filter([
            'id' => isset($t['id']) ? (string) $t['id'] : null,
            'source_path' => count($path) === 3 ? implode('/', $path) : null,
            'name' => $t['full_name'] ?? trim(implode(' ', array_filter([$t['first_name'] ?? null, $t['middle_name'] ?? null, $t['last_name'] ?? null]))),
            'designation' => $t['designation'] ?? null,
            // "Assistant Professor and Head" → the note "and Head"
            'designation_note' => self::note((string) ($t['designation'] ?? ''), (string) ($t['designation_title'] ?? '')),
            'department' => $department['code'] ?? null,
            'department_name' => $department['name'] ?? null,
            'email' => $t['email'] ?? null,
            'phone' => $t['phone'] ?? null,
            'office_room' => $t['office_room'] ?? null,
            'photo_url' => $t['photo_url'] ?? null,
            'employment_type' => str_contains($status, 'leave') ? 'on_leave' : (str_contains($status, 'part') ? 'part_time' : (str_contains($status, 'adjunct') ? 'adjunct' : null)),
            'status' => Str::contains($status, self::GONE) ? 'inactive' : 'active',
            'biography' => filled($t['bio'] ?? null) ? '<p>'.e((string) $t['bio']).'</p>' : null,
        ], fn ($v): bool => $v !== null && $v !== '');
    }

    protected static function note(string $designation, string $title): ?string
    {
        $title = trim($title);
        if ($title === '' || strcasecmp($title, trim($designation)) === 0) {
            return null;
        }

        return $designation !== '' && Str::startsWith(Str::lower($title), Str::lower($designation)) ? (trim(Str::substr($title, Str::length($designation))) ?: null) : $title;
    }
}
