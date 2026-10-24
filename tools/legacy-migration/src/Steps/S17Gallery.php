<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Legacy\Migration\Support\Normalizer;
use Modules\Department\Models\Department;
use Modules\Gallery\Filament\Components\AlbumItems;
use Modules\Gallery\Models\Album;
use Modules\Gallery\Models\Video;
use Modules\Gallery\Support\Speakers;
use Modules\Gallery\Support\VideoLink;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * S17G Gallery (DB-05 v1.6 §7, §18): `gallery_categories` → gallery.category, each
 * `gallery_sub_categories` row → an album (photos without one → an album named after their
 * category); department (`assign_department`, the photos' `department_id`) → the album's
 * relation, else the main site. The pictures are not copied: once S03 has put a legacy file in
 * the Media Library (id map "files", key = its path) the album uses that item, with
 * `photo_title` as its caption here — run this step again after S03. `documentaries` → one
 * "Documentaries" album per department, `lecture_series` → "Lecture Series" (episode, speaker as a
 * manual person), `program_videos` → "Program Videos" (its program to choose). `event_images` go
 * to the event (S16E), `lab_galleries` with Lab. Run after S04, S10.
 */
class S17Gallery extends Step
{
    public static function key(): string
    {
        return 'S17G';
    }

    public static function title(): string
    {
        return 'Gallery';
    }

    public static function requires(): array
    {
        return ['gallery'];
    }

    protected function handle(): void
    {
        if (! Schema::connection('legacy')->hasTable('gallery_categories')) {
            $this->report->review('No gallery tables in the legacy database: nothing to migrate.');

            return;
        }

        $vocabulary = app(Vocabularies::class)->find(Album::CATEGORY)->id;
        $categories = [];
        $categoryRows = $this->rows('gallery_categories', fn ($q) => $q->whereNull('deleted_at'))->keyBy('id');
        foreach ($categoryRows as $row) {
            $name = Normalizer::text($row->category_name);
            Vocabularies::ensureTerms(Album::CATEGORY, [['name' => ['en' => $name], 'slug' => Str::slug($name)]]);
            $categories[$row->id] = Term::query()->where('vocabulary_id', $vocabulary)->get()
                ->first(fn (Term $t): bool => $t->getTranslation('name', 'en', false) === $name)?->id;
        }

        $photos = $this->rows('gallery_photos', fn ($q) => $q->whereNull('deleted_at'))->groupBy(fn ($p) => $p->gallery_sub_category_id ? 'sub:'.$p->gallery_sub_category_id : 'cat:'.$p->gallery_category_id);
        $linked = 0;
        $waiting = 0;

        $albums = [];
        foreach ($this->rows('gallery_sub_categories') as $row) {
            $albums[] = ['table' => 'gallery_sub_categories', 'row' => $row, 'name' => $row->sub_category_name, 'category' => $row->gallery_category_id,
                'department' => $row->assign_department ?: ($categoryRows[$row->gallery_category_id]->assign_department ?? null), 'photos' => $photos->get('sub:'.$row->id, collect()),
                'active' => (bool) $row->publication_status];
        }
        foreach ($photos->keys()->filter(fn (string $k): bool => str_starts_with($k, 'cat:')) as $key) {
            $category = $categoryRows[(int) substr($key, 4)] ?? null;
            $albums[] = ['table' => 'gallery_categories@album', 'row' => (object) ['id' => (int) substr($key, 4)], 'name' => $category?->category_name ?? 'Gallery', 'category' => $category?->id,
                'department' => $category?->assign_department, 'photos' => $photos->get($key), 'active' => true];
        }

        foreach ($albums as $a) {
            $place = $this->place($a['department'] ?: $a['photos']->pluck('department_id')->filter()->first());
            $home = $a['photos']->contains(fn ($p) => (string) $p->is_home === '1');
            $this->upsert($a['table'], $a['row'], Album::class, [
                'title' => Normalizer::en(Normalizer::text($a['name'])),
                'is_active' => $a['active'],
            ], after: function (Album $album, bool $created) use ($a, $place, $home, $categories, &$linked, &$waiting): void {
                $album->syncTerms(Album::CATEGORY, array_filter([$categories[$a['category']] ?? null]));
                $this->show($album, $place, main: $place === null || $home);

                $rows = [];
                foreach ($a['photos'] as $photo) {
                    $item = $this->ids->record('files', (string) Normalizer::filePath($photo->photo_name), MediaItem::class);
                    $item ? $linked++ : $waiting++;
                    if ($item) {
                        $rows[] = ['media' => $item->id, 'caption' => array_filter(['en' => Normalizer::text($photo->photo_title)])];
                    }
                }
                if ($rows !== [] || $created) {
                    AlbumItems::save($album, $rows);
                }
            });
        }

        $this->videos();

        if ($waiting > 0) {
            $this->report->review("{$waiting} gallery photos wait for the files (S03): run S17G again after S03 — albums then use the Media Library items, no copies.");
        }
        $this->report->review("{$linked} gallery photos linked from the Media Library.");
        foreach (['event_images' => 'go to their event (S16E after S03)', 'lab_galleries' => 'come with the Lab module'] as $table => $when) {
            if (Schema::connection('legacy')->hasTable($table)) {
                $this->report->review($this->legacy()->table($table)->count()." {$table} {$when}.");
            }
        }
    }

    protected function videos(): void
    {
        if (Schema::connection('legacy')->hasTable('documentaries')) {
            foreach ($this->rows('documentaries')->groupBy(fn ($r) => (int) $r->department_id) as $department => $rows) {
                $place = $department ? $this->place($department) : null;
                if ($rows->every(fn ($r) => $r->lab_facility_id)) {
                    $rows->each(fn () => $this->report->skipped('documentaries', 'lab documentary — with the Lab module'));

                    continue;
                }
                $album = $this->upsert('documentaries@album', (object) ['id' => $department], Album::class, [
                    'title' => ['en' => 'Documentaries'], 'series_type' => 'documentary', 'is_active' => true,
                ], after: fn (Album $album) => $this->show($album, $place, main: $place === null));
                foreach ($rows->reject(fn ($r) => $r->lab_facility_id) as $row) {
                    $this->video('documentaries', $row, $album, $row->documentary_link ?: 'https://www.youtube.com/watch?v='.$row->video_code, $row->documentary_title, null, (bool) $row->publication_status);
                }
            }
        }

        if (Schema::connection('legacy')->hasTable('lecture_series')) {
            $album = $this->upsert('lecture_series@album', (object) ['id' => 1], Album::class, [
                'title' => ['en' => 'Lecture Series'], 'series_type' => 'lecture_series', 'is_active' => true,
            ], after: fn (Album $album) => $this->show($album, null, main: true));
            foreach ($this->rows('lecture_series') as $row) {
                $video = $this->video('lecture_series', $row, $album, $row->video_link, null, (int) $row->lecture_no ?: null, (bool) $row->publication_status, (int) $row->serial);
                if ($video && filled($row->name)) {
                    $designation = implode(', ', array_filter([Normalizer::text($row->designation), Normalizer::text($row->company)]));
                    app(Speakers::class)->set($video, $this->manualPerson('lecture_series@person', $row, $row->name, $designation, null, null));
                }
            }
        }

        if (Schema::connection('legacy')->hasTable('program_videos')) {
            $album = $this->upsert('program_videos@album', (object) ['id' => 1], Album::class, [
                'title' => ['en' => 'Program Videos'], 'is_active' => true,
            ]);
            foreach ($this->rows('program_videos') as $row) {
                $this->video('program_videos', $row, $album, 'https://www.youtube.com/watch?v='.$row->code, $row->title, null, (bool) $row->publication_status);
            }
            $this->report->review('Album "Program Videos" (MBA videos) is shown nowhere: choose its program under Content › Gallery.');
        }
    }

    protected function video(string $table, object $row, Album $album, ?string $url, ?string $title, ?int $episode, bool $active, int $order = 0): ?Video
    {
        if (blank($url) || ! VideoLink::parse((string) $url)) {
            $this->report->skipped($table, 'no YouTube / Vimeo / Facebook link');

            return null;
        }

        return $this->upsert($table, $row, Video::class, [
            'album_id' => $album->id,
            'url' => trim((string) $url),
            'title' => Normalizer::en(Normalizer::text($title)),
            'episode_no' => $episode,
            'sort_order' => $order,
            'is_active' => $active,
        ]);
    }

    protected function place(int|string|null $departmentId): ?Model
    {
        if (blank($departmentId) || ! is_numeric($departmentId)) {
            return null;
        }
        $office = 'Modules\\Office\\Models\\Office';

        return $this->ids->record('departments', (int) $departmentId, Department::class)
            ?? (class_exists($office) ? $this->ids->record('departments', (int) $departmentId, $office) : null);
    }

    protected function show(Album $album, ?Model $place, bool $main): void
    {
        app(RelationEngine::class)->sync($album, $place ? [$place->getMorphClass().':'.$place->getKey()] : [], mainSite: $main, user: null);
    }
}
