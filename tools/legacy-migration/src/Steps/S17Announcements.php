<?php

namespace Legacy\Migration\Steps;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Legacy\Migration\Support\Normalizer;
use Modules\Announcement\Models\Widget;
use Modules\Department\Models\Department;
use Modules\Faculty\Models\Faculty;
use Modules\Program\Models\Program;
use Platform\Media\MediaItem;
use Platform\Relations\RelationEngine;

/**
 * S17A Announcements (DB-05 v1.11 §15, §18): `greetings` → popups — where from the flags (`is_home`
 * main site, `is_faculty` / `is_department` / `is_program` their entity, `is_admission` the pages
 * under /admission), from / until from start / expiry date, the picture from the Media Library
 * after S03 (id map "files"); `news_tickers` → ticker lines of their department (or the main site
 * when `is_home`); `countdown_timers` → countdowns. Run after S04, S06, S10.
 */
class S17Announcements extends Step
{
    public static function key(): string
    {
        return 'S17A';
    }

    public static function title(): string
    {
        return 'Announcements';
    }

    public static function requires(): array
    {
        return ['announcement'];
    }

    protected function handle(): void
    {
        $waiting = 0;

        if (Schema::connection('legacy')->hasTable('greetings')) {
            foreach ($this->rows('greetings', fn ($q) => $q->whereNull('deleted_at')) as $row) {
                $places = array_filter([
                    $row->is_faculty ? $this->ids->record('faculties', $row->faculty_id, Faculty::class) : null,
                    $row->is_department ? $this->department($row->department_id) : null,
                    $row->is_program ? $this->ids->record('department_program_type', $row->department_program_type_id, Program::class) : null,
                ]);
                $admission = (bool) $row->is_admission;
                $this->widget('greetings', $row, [
                    'widget_type' => 'popup',
                    'title' => Normalizer::en(Normalizer::text($row->greeting_title)),
                    'body' => Normalizer::en(Normalizer::html($row->greeting_content)),
                    'link_url' => Normalizer::url($row->greeting_url),
                    'frequency' => 'daily',
                    'pages' => $admission ? 'selected' : 'home',
                    'route_keys' => $admission ? ['/admission'] : null,
                    'active_at' => $row->start_date,
                    'expires_at' => $row->expiry_date && $row->start_date && $row->expiry_date > $row->start_date ? $row->expiry_date : null,
                    'is_active' => (bool) $row->publication_status,
                ], $places, main: (string) $row->is_home === '1' || $admission || $places === [], picture: $row->greeting_name, waiting: $waiting);
            }
        }

        if (Schema::connection('legacy')->hasTable('news_tickers')) {
            foreach ($this->rows('news_tickers') as $row) {
                $place = $this->department($row->department_id);
                $this->widget('news_tickers', $row, [
                    'widget_type' => 'ticker',
                    'title' => Normalizer::en(Normalizer::text($row->news_title)),
                    'body' => Normalizer::en(Normalizer::text($row->news_title)),
                    'link_url' => Normalizer::url($row->news_link),
                    'pages' => 'home',
                    'sort_order' => (int) $row->news_serial,
                    'is_active' => (bool) $row->publication_status,
                ], array_filter([$place]), main: (string) $row->is_home === '1' || $place === null);
            }
        }

        if (Schema::connection('legacy')->hasTable('countdown_timers')) {
            foreach ($this->rows('countdown_timers') as $row) {
                $place = $this->department($row->department_id);
                $this->widget('countdown_timers', $row, [
                    'widget_type' => 'countdown',
                    'title' => Normalizer::en(Normalizer::text($row->counter_title)),
                    'target_at' => $row->event_date,
                    'link_url' => Normalizer::url($row->event_link),
                    'pages' => 'home',
                    'is_active' => (bool) $row->publication_status,
                ], array_filter([$place]), main: (string) $row->is_home === '1' || $place === null);
            }
        }

        if ($waiting > 0) {
            $this->report->review("{$waiting} popup pictures wait for the files (S03): run S17A again after S03.");
        }
    }

    /** @param list<Model> $places */
    protected function widget(string $table, object $row, array $attributes, array $places, bool $main, ?string $picture = null, int &$waiting = 0): void
    {
        if (blank($attributes['title']['en'] ?? null)) {
            $this->report->skipped($table, 'no title');

            return;
        }
        if (filled($attributes['link_url'] ?? null) && ! preg_match('#^(https?://\S+|/\S*)$#i', (string) $attributes['link_url'])) {
            $attributes['link_url'] = null;
        }

        $this->upsert($table, $row, Widget::class, $attributes, after: function (Widget $widget) use ($places, $main, $picture, &$waiting): void {
            app(RelationEngine::class)->sync($widget, array_map(fn (Model $p): string => $p->getMorphClass().':'.$p->getKey(), $places), mainSite: $main, user: null);
            if (filled($picture)) {
                $item = $this->ids->record('files', (string) $picture, MediaItem::class);
                $item ? $widget->syncMedia('image', [$item->id]) : $waiting++;
            }
        });
    }

    protected function department(int|string|null $id): ?Model
    {
        if (blank($id)) {
            return null;
        }
        $office = 'Modules\\Office\\Models\\Office';

        return $this->ids->record('departments', (int) $id, Department::class)
            ?? (class_exists($office) ? $this->ids->record('departments', (int) $id, $office) : null);
    }
}
