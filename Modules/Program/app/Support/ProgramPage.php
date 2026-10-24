<?php

namespace Modules\Program\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Program\Models\Program;
use Modules\Program\Models\ProgramListItem;
use Platform\Media\MediaItem;
use Platform\Statistics\Statistics;
use Platform\Website\EntityTabs;

/**
 * What a program page shows, ready for a theme (`$page` in program::microsite.home): the parts
 * that have something, in their order, the other modules' tabs by key, the figures and the facts.
 * Themes hold no PHP logic (T-1), so everything worked out lives here.
 */
class ProgramPage
{
    /** Tabs the page places itself; any other follows them under its own label */
    public const PLACED_TABS = ['accreditation.program', 'facilities.program', 'testimonials.program', 'tuition.fee'];

    protected array $memo = [];

    public function __construct(public Program $program) {}

    /** @return Collection<string, array{key: string, label: string, html: string}> rendered tabs by key */
    public function tabs(): Collection
    {
        return $this->memo['tabs'] ??= collect(app(EntityTabs::class)->render($this->program))->keyBy('key');
    }

    /** @return array{key: string, label: string, html: string}|null */
    public function tab(string $key): ?array
    {
        return $this->tabs()->get($key);
    }

    /** @return Collection<string, array{key: string, label: string, html: string}> */
    public function otherTabs(): Collection
    {
        return $this->tabs()->except(self::PLACED_TABS);
    }

    /** @return array<string, string> objective and the credit text; the summary only when neither is there */
    public function overview(): array
    {
        $parts = array_filter(['objective' => $this->program->objective, 'credit_duration' => $this->program->credit_duration], 'filled');

        return array_map(fn ($html) => Str::sanitizeHtml((string) $html), $parts ?: array_filter(['summary' => $this->program->summary], 'filled'));
    }

    /** @return array<string, string> general / english_medium eligibility, sanitized */
    public function eligibility(): array
    {
        return array_map(fn ($html) => Str::sanitizeHtml((string) $html), array_filter([
            'general' => $this->program->eligibility_general,
            'english_medium' => $this->program->eligibility_english_medium,
        ], 'filled'));
    }

    public function variants(): Collection
    {
        return $this->memo['variants'] ??= $this->program->variants()->frontendVisible()->with('relatedProgram')->get();
    }

    /** @return Collection<string, Collection> majors by category name ('' = none), in order */
    public function majorGroups(): Collection
    {
        return $this->memo['majors'] ??= $this->program->majors()->frontendVisible()->with('category')->get()->groupBy(fn ($m) => $m->category?->name ?? '');
    }

    public function memberships(): Collection
    {
        return $this->memo['memberships'] ??= $this->program->memberships()->frontendVisible()->get();
    }

    public function facilitators(): Collection
    {
        return $this->memo['facilitators'] ??= $this->program->facilitators()->frontendVisible()->get();
    }

    /** @return array<string, Collection> career_paths / industries that have items */
    public function careers(): array
    {
        $lists = $this->memo['lists'] ??= $this->program->listItems()->frontendVisible()->get()->groupBy('list_type');

        return array_filter(['career_paths' => $lists->get(ProgramListItem::CAREER_PATH), 'industries' => $lists->get(ProgramListItem::INDUSTRY)]);
    }

    /** Materials with an address */
    public function materials(): Collection
    {
        return $this->memo['materials'] ??= $this->program->materials()->frontendVisible()->get()->filter(fn ($m) => $m->href())->values();
    }

    public function brochure(): ?MediaItem
    {
        return $this->program->firstMedia('brochure');
    }

    /** The hero picture: the program's, else its department's cover or photo */
    public function image(): ?MediaItem
    {
        $department = $this->program->department;

        return $this->program->firstMedia('image') ?? $department?->firstMedia('cover') ?? $department?->firstMedia('photo');
    }

    public function youtubeId(): ?string
    {
        return preg_match('~(?:youtu\.be/|v/|u/\w/|embed/|watch\?v=|&v=)([\w-]{11})~', (string) $this->program->video_url, $m) ? $m[1] : null;
    }

    /** @return list<array{label: string, value: float, display: string, suffix: string}> the program's counters */
    public function stats(): array
    {
        return array_map(fn (array $stat): array => [...$stat, 'value' => (float) $stat['value'], 'suffix' => preg_replace('/[\d.,\s]/u', '', (string) $stat['display'])],
            app(Statistics::class)->forOwner($this->program->getMorphClass().':'.$this->program->getKey()));
    }

    /** @return array<string, string> key facts: credits, semesters (else duration), total fees, next start, by key */
    public function facts(): array
    {
        $p = $this->program;

        return array_filter([
            'credits' => Program::plainNumber($p->total_credits),
            'semesters' => $p->total_terms ? (string) $p->total_terms : null,
            'duration' => $p->total_terms ? null : $p->durationText(),
            'total_fees' => $this->totalFees(),
            'next_start' => $p->next_intake_on?->toDateString(),
        ], 'filled');
    }

    /** The first fee category's total from the Tuition module, if active */
    public function totalFees(): ?string
    {
        $tab = collect(app(EntityTabs::class)->for($this->program))->first(fn ($t) => $t::key() === 'tuition.fee' && method_exists($t, 'data'));
        $tuition = $tab ? ($tab->data($this->program)['tuitions'] ?? collect())->first() : null;

        return $tuition && $tuition->total_min !== null ? $tuition->category?->money($tuition->total_min) : null;
    }

    /** AI summary as plain text, and its first ~400 characters when longer */
    public function aiSummary(): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags(html_entity_decode((string) $this->program->ai_summary))));

        return $text === '' ? null : $text;
    }

    public function aiSummaryShort(int $length = 400): ?string
    {
        $text = $this->aiSummary();

        return $text === null || mb_strlen($text) <= $length ? null : Str::beforeLast(mb_substr($text, 0, $length), ' ').'...';
    }

    /**
     * The parts that have something, in DIU's order: anchor, key (a theme's label) and a name
     * of its own (major category, tab label) where there is one.
     *
     * @return list<array{anchor: string, key: string, name: ?string}>
     */
    public function sections(): array
    {
        $s = [];
        $add = function (string $anchor, string $key, ?string $name = null) use (&$s): void {
            $s[] = ['anchor' => $anchor, 'key' => $key, 'name' => $name];
        };

        if ($this->overview()) {
            $add('overview', 'overview');
        }
        if ($this->variants()->isNotEmpty()) {
            $add('variants', 'variants');
        }
        foreach ($this->majorGroups()->keys()->values() as $i => $category) {
            $add('majors-'.($i + 1), 'majors', $category !== '' ? $category : null);
        }
        if ($this->tab('accreditation.program')) {
            $add('accreditation-program', 'accreditation');
        }
        if ($this->eligibility()) {
            $add('eligibilities', 'eligibility');
        }
        if ($this->memberships()->isNotEmpty()) {
            $add('memberships', 'memberships');
        }
        if ($this->tab('facilities.program')) {
            $add('facilities-program', 'facilities');
        }
        if ($this->facilitators()->isNotEmpty()) {
            $add('facilitators', 'facilitators');
        }
        if ($this->careers()) {
            $add('future-career', 'future_career');
        }
        if ($this->tab('testimonials.program')) {
            $add('testimonials-program', 'testimonials');
        }
        if ($this->tab('tuition.fee')) {
            $add('tuition-fee', 'tuition_fees');
        }
        foreach ($this->otherTabs() as $key => $tab) {
            $add(str_replace('.', '-', $key), 'tab', $tab['label']);
        }

        return $s;
    }

    /** @return list<array{label: string, url: string}> the program's own Quick Links, else Settings › Programs' */
    public function quickLinks(): array
    {
        return link_list($this->program->quick_links) ?: link_list(settings('program.page_quick_links'));
    }
}
