<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Program\Models\Program;
use Modules\Slider\Models\Slide;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;

/**
 * S17S Slider (DB-05 v1.7 §8, §18): `sliders` → hero slides shown in their department (and the
 * `show_departments`), on the main site when `is_home`; `cover_photos` → page banners;
 * `program_sliders` → the program's hero (bullets, two buttons); `mba_sliders` → hero slides to
 * place by hand. Pictures are not copied: once S03 has put a legacy file in the Media Library (id
 * map "files", key = its path) the slide uses it — run again after S03. The main homepage's
 * `core.hero` section becomes `slider.hero` when main-site slides exist. Run after S04, S06, S10.
 */
class S17Slider extends Step
{
    protected int $waiting = 0;

    public static function key(): string
    {
        return 'S17S';
    }

    public static function title(): string
    {
        return 'Slider';
    }

    public static function requires(): array
    {
        return ['slider'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('sliders')) {
            $this->report->review('No sliders table in the legacy database: nothing to migrate.');

            return;
        }

        foreach ($this->rows('sliders', fn ($q) => $q->whereNull('deleted_at')) as $row) {
            if ($row->lab_facility_id) {
                $this->report->skipped('sliders', 'lab slider — with the Lab module');

                continue;
            }
            $places = collect([$row->department_id, ...explode(',', (string) $row->show_departments)])
                ->map(fn ($id) => $this->department($id))->filter()->unique(fn (Model $m) => $m->getMorphClass().$m->getKey())->values();
            $button = filled($row->url) && filled($row->button_text) ? [['label' => ['en' => Normalizer::text($row->button_text)], 'url' => trim((string) $row->url)]] : null;

            $this->slide('sliders', $row, [
                'placement' => 'hero',
                'title' => Normalizer::en(Normalizer::text($row->slider_title)),
                'subtitle' => Normalizer::en(Normalizer::text($row->slider_text)),
                'buttons' => $button,
                'sort_order' => (int) $row->serial,
                'is_active' => (bool) $row->publication_status,
            ], $row->slider_name, $places->all(), main: (string) $row->is_home === '1');
        }

        if (Schema::connection('legacy')->hasTable('cover_photos')) {
            foreach ($this->rows('cover_photos') as $row) {
                $this->slide('cover_photos', $row, [
                    'placement' => 'page_banner',
                    'is_active' => (bool) $row->publication_status,
                ], $row->photo, array_filter([$this->department($row->department_id)]), main: false);
            }
        }

        if (Schema::connection('legacy')->hasTable('program_sliders')) {
            foreach ($this->rows('program_sliders') as $row) {
                $program = $this->ids->record('department_program_type', $row->department_program_type_id, Program::class);
                $buttons = collect([[$row->first_button_text, $row->first_button_link], [$row->second_button_text, $row->second_button_link]])
                    ->filter(fn (array $b): bool => filled($b[0]) && filled($b[1]))
                    ->map(fn (array $b): array => ['label' => ['en' => Normalizer::text($b[0])], 'url' => trim((string) $b[1])])->values()->all();
                $bullets = array_values(array_filter(array_map(fn ($p) => Normalizer::text($p), [$row->first_point, $row->second_point, $row->third_point, $row->fourth_point])));

                $this->slide('program_sliders', $row, [
                    'placement' => 'hero',
                    'title' => Normalizer::en(Normalizer::text($row->long_title) ?: Normalizer::text($row->short_title)),
                    'subtitle' => filled($row->long_title) ? Normalizer::en(Normalizer::text($row->short_title)) : null,
                    'buttons' => $buttons ?: null,
                    'bullets' => $bullets ? ['en' => $bullets] : null,
                    'sort_order' => (int) $row->serial,
                    'is_active' => (bool) $row->publication_status,
                ], $row->image, array_filter([$program]), main: false);
                $program ?? $this->report->review("program_sliders #{$row->id}: its program is not migrated (run S06 first) — shown nowhere.");
            }
        }

        if (Schema::connection('legacy')->hasTable('mba_sliders')) {
            foreach ($this->rows('mba_sliders') as $row) {
                $this->slide('mba_sliders', $row, [
                    'placement' => 'hero',
                    'title' => Normalizer::en(Normalizer::text($row->title)),
                    'subtitle' => Normalizer::en(Normalizer::text($row->description)),
                    'buttons' => filled($row->link) ? [['label' => ['en' => 'Learn more'], 'url' => trim((string) $row->link)]] : null,
                    'sort_order' => (int) $row->serial,
                    'is_active' => (bool) $row->publication_status,
                ], $row->image, [], main: false);
            }
            $this->report->review('The 4 mba_sliders are shown nowhere: choose the MBA program under Content › Slider › Show in.');
        }

        $this->useOnMainHomepage();

        if ($this->waiting > 0) {
            $this->report->review("{$this->waiting} slide pictures wait for the files (S03): run S17S again after S03 — slides then use the Media Library items, no copies.");
        }
    }

    /** @param  list<Model>  $places */
    protected function slide(string $table, object $row, array $attributes, ?string $picture, array $places, bool $main): void
    {
        $this->upsert($table, $row, Slide::class, $attributes + ['text_position' => 'left', 'overlay_opacity' => 40],
            after: function (Slide $slide) use ($picture, $places, $main): void {
                app(RelationEngine::class)->sync($slide, array_map(fn (Model $p): string => $p->getMorphClass().':'.$p->getKey(), $places), mainSite: $main, user: null);
                $item = filled($picture) ? $this->ids->record('files', (string) $picture, MediaItem::class) : null;
                $item ? $slide->syncMedia('image_desktop', [$item->id]) : $this->waiting++;
            });
    }

    protected function department(int|string|null $id): ?Model
    {
        if (blank($id) || ! is_numeric(trim((string) $id))) {
            return null;
        }
        $office = 'Modules\\Office\\Models\\Office';

        return $this->ids->record('departments', (int) $id, Department::class)
            ?? (class_exists($office) ? $this->ids->record('departments', (int) $id, $office) : null);
    }

    /** DIU's homepage hero comes from these slides now: its core.hero section becomes slider.hero. */
    protected function useOnMainHomepage(): void
    {
        $homepages = 'Modules\\Homepage\\Support\\Homepages';
        if (! class_exists($homepages)) {
            return;
        }
        $hasSlides = app(RelationEngine::class)->for(RelationEngine::SITE_KEY)->content(Slide::class)->query()->where('placement', 'hero')->exists();
        $main = app($homepages)->own(RelationEngine::SITE_KEY);
        if (! $hasSlides || $main === null) {
            return;
        }
        $changed = $main->sections()->where('section_type', 'core.hero')->get()
            ->each(fn ($s) => $s->update(['section_type' => 'slider.hero', 'settings' => ['autoplay' => true, 'interval' => 6, 'transition' => 'fade', 'arrows' => true, 'dots' => true, 'height' => 'large']]))
            ->count();
        if ($changed > 0) {
            $this->report->review('Main homepage: the Hero section now shows the slides from Content › Slider.');
        }
    }
}
