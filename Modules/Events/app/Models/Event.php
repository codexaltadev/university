<?php

namespace Modules\Events\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Events\Database\Factories\EventFactory;
use Platform\Concerns\AppliesUserScope;
use Platform\Concerns\HasActiveAt;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSeo;
use Platform\Concerns\HasSlug;
use Platform\Concerns\HasTaxonomy;
use Platform\Concerns\HasViewCount;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsAttachable;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\Scopeable;
use Platform\Models\BaseModel;
use Platform\Seo\Seo;
use Spatie\Translatable\HasTranslations;

/**
 * An event (M-19, DB-05 §4 v1.3, SRS Content FR-EVT-01..10): seminar, workshop, convocation …
 * One start (required) and an optional end, all day or timed, venue, online link, organizer,
 * speakers (assignments), registration with a closing time, gallery afterwards. Upcoming /
 * ongoing / past come from the dates (FR-EVT-02, AC-EVT-01); where it shows is "Show in" and
 * the main site (Admin, Q-26).
 */
class Event extends BaseModel implements Scopeable
{
    use AppliesUserScope;   // entity users: events shown in their entities
    use HasActiveAt;        // when it starts to show on the website

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasMediaUsage;      // featured_image, gallery
    use HasSeo;
    use HasSlug;            // /events/{slug}
    use HasTaxonomy;        // events.category
    use HasTranslations;
    use HasViewCount;
    use IsAssignable;       // speakers, moderators
    use IsAttachable;       // "Show in", main site, highlight
    use IsSearchable;

    public const PATH = 'events';

    public const CATEGORY = 'events.category';

    public const ROLES = ['speaker', 'moderator'];

    public static string $titleAttribute = 'title';

    protected string $slugSource = 'title';

    protected $table = 'events_events';

    protected $fillable = [
        'title', 'summary', 'body', 'starts_at', 'ends_at', 'is_all_day', 'venue', 'online_url', 'organizer_label',
        'registration_url', 'registration_ends_at', 'active_at', 'is_active',
    ];

    public array $translatable = ['title', 'summary', 'body', 'venue', 'organizer_label'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'registration_ends_at' => 'datetime', 'is_all_day' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->starts_at === null) {
                throw ValidationException::withMessages(['starts_at' => __('events::events.errors.no_start')]);   // FR-EVT-01
            }
            if ($event->ends_at !== null && $event->ends_at->lt($event->starts_at)) {
                throw ValidationException::withMessages(['ends_at' => __('events::events.errors.end_before_start')]);
            }
        });
        static::creating(function (Event $event): void {
            $event->active_at ??= now();   // every event has a "show from" (context lists order by it)
        });
    }

    /** The moment it is over: its end; else the end of its day when all day; else its start. */
    public function finishesAt(): Carbon
    {
        return $this->ends_at ?? ($this->is_all_day ? $this->starts_at->copy()->endOfDay() : $this->starts_at);
    }

    /** upcoming / ongoing / past (FR-EVT-02). */
    public function state(?Carbon $now = null): string
    {
        $now ??= now();

        return match (true) {
            $this->starts_at->gt($now) => 'upcoming',
            $this->finishesAt()->gte($now) => 'ongoing',
            default => 'past',
        };
    }

    /** Not over yet: upcoming and ongoing. */
    public function scopeUpcoming(Builder $query): Builder
    {
        $now = now();
        $start = $this->qualifyColumn('starts_at');
        $end = $this->qualifyColumn('ends_at');

        return $query->where(fn (Builder $q) => $q->where($start, '>', $now)
            ->orWhere($end, '>=', $now)
            ->orWhere(fn (Builder $w) => $w->whereNull($end)->where($this->qualifyColumn('is_all_day'), true)->where($start, '>=', $now->copy()->startOfDay()))
            ->orWhere(fn (Builder $w) => $w->whereNull($end)->where($this->qualifyColumn('is_all_day'), false)->where($start, '>=', $now)))
            ->orderBy($start);
    }

    public function scopePast(Builder $query): Builder
    {
        $now = now();
        $start = $this->qualifyColumn('starts_at');
        $end = $this->qualifyColumn('ends_at');

        return $query->where(fn (Builder $q) => $q->where($end, '<', $now)
            ->orWhere(fn (Builder $w) => $w->whereNull($end)->where($this->qualifyColumn('is_all_day'), true)->where($start, '<', $now->copy()->startOfDay()))
            ->orWhere(fn (Builder $w) => $w->whereNull($end)->where($this->qualifyColumn('is_all_day'), false)->where($start, '<', $now)))
            ->orderByDesc($start);
    }

    public function registrationOpen(): bool
    {
        return filled($this->registration_url) && ($this->registration_ends_at === null || $this->registration_ends_at->isFuture()) && $this->state() !== 'past';
    }

    /** "15 December 2026, 10:00 – 13:00", "15 – 17 December 2026", "15 December 2026" */
    public function when(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $start = $this->starts_at->copy()->locale($locale);
        $end = $this->ends_at?->copy()->locale($locale);
        $time = fn (Carbon $c): string => $c->translatedFormat('H:i');

        if ($this->is_all_day) {
            return match (true) {
                $end === null || $start->isSameDay($end) => $start->translatedFormat('j F Y'),
                $start->isSameMonth($end) => $start->translatedFormat('j').' – '.$end->translatedFormat('j F Y'),
                default => $start->translatedFormat('j F Y').' – '.$end->translatedFormat('j F Y'),
            };
        }

        return match (true) {
            $end === null => $start->translatedFormat('j F Y').', '.$time($start),
            $start->isSameDay($end) => $start->translatedFormat('j F Y').', '.$time($start).' – '.$time($end),
            default => $start->translatedFormat('j F Y, H:i').' – '.$end->translatedFormat('j F Y, H:i'),
        };
    }

    /** Google Calendar "add" link (FR-EVT-07). */
    public function googleCalendarUrl(?string $locale = null): string
    {
        $format = $this->is_all_day ? 'Ymd' : 'Ymd\THis\Z';
        $start = $this->is_all_day ? $this->starts_at->copy() : $this->starts_at->copy()->utc();
        $end = $this->is_all_day ? ($this->ends_at ?? $this->starts_at)->copy()->addDay() : ($this->ends_at ?? $this->starts_at->copy()->addHour())->copy()->utc();

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $this->getTranslation('title', $locale ?? app()->getLocale(), true),
            'dates' => $start->format($format).'/'.$end->format($format),
            'details' => url($this->url($locale)),
            'location' => (string) $this->getTranslation('venue', $locale ?? app()->getLocale(), true),
        ]);
    }

    /** /events/{slug} on the main site; an event only shown in entities lives under the first one's microsite. */
    public function url(?string $locale = null): string
    {
        $slug = $this->slug($locale) ?? (string) $this->getKey();
        if (! $this->isOnMainSite()) {
            foreach ($this->targetKeys() as $key) {
                [$type, $id] = explode(':', $key, 2);
                $model = Relation::getMorphedModel($type);
                $entity = $model ? $model::query()->withoutGlobalScopes()->find($id) : null;
                if ($entity && ($base = app(MicrositeResolver::class)->urlFor($entity, $locale))) {
                    return rtrim($base, '/').'/'.self::PATH.'/'.$slug;
                }
            }
        }

        return '/'.self::PATH.'/'.$slug;
    }

    protected function searchBodyAttributes(): array
    {
        return ['summary', 'body', 'venue'];
    }

    /** FR-EVT-09 */
    protected function jsonLd(string $locale): array
    {
        return array_filter([
            '@type' => 'Event',
            'name' => $this->getTranslation('title', $locale, true),
            'description' => $this->getTranslation('summary', $locale, true) ?: null,
            'startDate' => $this->is_all_day ? $this->starts_at->toDateString() : $this->starts_at->toIso8601String(),
            'endDate' => $this->ends_at ? ($this->is_all_day ? $this->ends_at->toDateString() : $this->ends_at->toIso8601String()) : null,
            'eventAttendanceMode' => filled($this->online_url) ? (filled($this->venue) ? 'https://schema.org/MixedEventAttendanceMode' : 'https://schema.org/OnlineEventAttendanceMode') : 'https://schema.org/OfflineEventAttendanceMode',
            'location' => filled($venue = $this->getTranslation('venue', $locale, true)) ? ['@type' => 'Place', 'name' => $venue] : (filled($this->online_url) ? ['@type' => 'VirtualLocation', 'url' => $this->online_url] : null),
            'organizer' => ($org = $this->getTranslation('organizer_label', $locale, true)) ? ['@type' => 'Organization', 'name' => $org] : null,
            'image' => $this->firstMedia('featured_image')?->url('large'),
            'url' => Seo::absolute(Seo::localized($this->url($locale), $locale)),
        ]);
    }

    protected static function newFactory(): EventFactory
    {
        return EventFactory::new();
    }
}
