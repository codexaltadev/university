<?php

namespace Modules\Program\Support;

use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramListItem;
use Platform\Localization\Languages;

/**
 * What is filled in on a program (UI/UX Part A §5.2 v1.2): the editor's tabs show it
 * (✓ / ! or the number of items) and the page says "6 of 7 parts filled in", so an
 * editor sees what is still missing. Options and SEO are optional and not counted.
 */
class ProgramChecklist
{
    /** Parts counted in the progress. */
    public const COUNTED = ['basic', 'details', 'eligibility', 'majors', 'careers', 'people', 'resources'];

    /** Parts that are lists: their badge is the number of items. */
    public const LISTS = ['majors', 'variants', 'careers', 'people', 'resources'];

    public static function count(Program $program, string $part): int
    {
        return match ($part) {
            'majors' => $program->majors()->count(),
            'variants' => $program->variants()->count(),
            'careers' => $program->listItems()->whereIn('list_type', [ProgramListItem::CAREER_PATH, ProgramListItem::INDUSTRY])->count(),
            'people' => $program->facilitators()->count(),
            'resources' => $program->materials()->count() + $program->memberships()->count(),
            default => 0,
        };
    }

    public static function filled(Program $program, string $part): bool
    {
        $text = fn (string $attribute): bool => filled(strip_tags((string) $program->getTranslation($attribute, app(Languages::class)->default(), false)));

        return match ($part) {
            'basic' => $program->mediaIdsIn('image') !== [],
            'details' => $text('summary') && filled($program->total_credits),
            'eligibility' => $text('eligibility_general'),
            'variants', 'seo' => true,
            default => self::count($program, $part) > 0,
        };
    }

    public static function badge(Program $program, string $part): ?string
    {
        if (in_array($part, self::LISTS, true)) {
            return (string) self::count($program, $part);
        }

        return in_array($part, self::COUNTED, true) ? (self::filled($program, $part) ? '✓' : '!') : null;
    }

    public static function color(Program $program, string $part): string
    {
        if (! in_array($part, self::COUNTED, true)) {
            return 'gray';
        }

        return self::filled($program, $part) ? 'success' : 'warning';
    }

    /** @return array{done: int, total: int} */
    public static function progress(Program $program): array
    {
        $done = collect(self::COUNTED)->filter(fn (string $part): bool => self::filled($program, $part))->count();

        return ['done' => $done, 'total' => count(self::COUNTED)];
    }
}
