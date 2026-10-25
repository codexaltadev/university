<?php

namespace Modules\Program\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;

/**
 * What the "Admission eligibility" block shows, ready for a theme (`$eligibility` in program::blocks.eligibility): the
 * faculty, its listed programs by level in the vocabulary's order (each with its short name and its eligibility for
 * general and English-medium students) and the block's links. Themes hold no PHP logic (T-1).
 */
class EligibilityPage
{
    public function __construct(protected array $data = []) {}

    protected function faculty(): ?Faculty
    {
        $id = (int) ($this->data['data']['faculty'] ?? 0);

        return $id ? Faculty::query()->frontendVisible()->find($id) : null;
    }

    /** @return array{name: string, overview: ?string}|null */
    public function head(): ?array
    {
        if (! $faculty = $this->faculty()) {
            return null;
        }
        $overview = trim(html_entity_decode(strip_tags((string) $faculty->overview)));

        $name = (string) $faculty->name;

        return ['name' => Str::startsWith(Str::lower($name), 'faculty') ? $name : __('program::program.eligibility_block.faculty_of', ['name' => $name]), 'overview' => $overview !== '' ? $overview : null];
    }

    /**
     * @return list<array{key: string, label: string, count: int, programs: list<array{name: string, short: ?string,
     *     general: ?string, english: ?string, url: string}>}>
     */
    public function levels(): array
    {
        if (! $faculty = $this->faculty()) {
            return [];
        }

        return Program::query()->listed()->with('level')
            ->whereHas('department', fn (Builder $d) => $d->where('faculty_id', $faculty->id))
            ->get()
            ->groupBy('level_term_id')
            ->sortBy(fn ($programs) => [$programs->first()->level?->sort_order ?? PHP_INT_MAX, $programs->first()->level_term_id])
            ->map(fn ($programs, $levelId): array => [
                'key' => 'level-'.$levelId,
                'label' => __('program::program.eligibility_block.level', ['level' => (string) ($programs->first()->level?->name ?? '')]),
                'count' => $programs->count(),
                'programs' => $programs->map(fn (Program $p): array => [
                    'name' => (string) $p->name,
                    'short' => filled($p->short_name) ? (string) $p->short_name : null,
                    'general' => filled($p->eligibility_general) ? Str::sanitizeHtml((string) $p->eligibility_general) : null,
                    'english' => filled($p->eligibility_english_medium) ? Str::sanitizeHtml((string) $p->eligibility_english_medium) : null,
                    'url' => url($p->url()),
                ])->values()->all(),
            ])->values()->all();
    }

    /** @return list<array{label: string, url: string}> */
    public function links(): array
    {
        return link_list((array) ($this->data['data']['links'] ?? []));
    }
}
