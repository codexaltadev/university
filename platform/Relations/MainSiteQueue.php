<?php

namespace Platform\Relations;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * "Main Site Queue" (FR-CNT-H08/H09, UI/UX Part A §7.3): content shown in entities
 * but not on the main site, of every attachable type the user may publish
 * (PublishMain). Requested items first, then the newest. Dismissed items leave the queue.
 *
 * Each item: key "news.post:12", type, type_label, id, title, places, shown_at, requested, requested_by, url.
 */
class MainSiteQueue
{
    public function __construct(protected RelationEngine $engine, protected Targets $targets, protected Registry $registry) {}

    /** @return array<string, class-string<Model>> alias => model, the attachable types this user may publish */
    public function types(?User $user): array
    {
        return collect($this->registry->all('attachable'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']])
            ->filter(fn (string $model): bool => $this->engine->mayPublishMain($user, $model))
            ->all();
    }

    /**
     * @param  array{type?: ?string, place?: ?string, days?: ?int}  $filters
     * @return Collection<string, array<string, mixed>> key => item
     */
    public function items(?User $user, array $filters = [], ?string $search = null): Collection
    {
        $types = $this->types($user);

        if (! empty($filters['type'])) {
            $types = array_intersect_key($types, [$filters['type'] => true]);
        }

        if ($types === []) {
            return collect();
        }

        $since = ! empty($filters['days']) ? now()->subDays((int) $filters['days']) : null;

        $rows = DB::table('core_relations as r')
            ->select('r.content_type', 'r.content_id', DB::raw('MAX(r.created_at) as shown_at'))
            ->where('r.target_type', '!=', RelationEngine::SITE_TYPE)
            ->whereIn('r.content_type', array_keys($types))
            ->when(! empty($filters['place']), function (Builder $q) use ($filters): void {
                [$type, $id] = explode(':', $filters['place'], 2);
                $q->whereExists(fn (Builder $p) => $p->selectRaw('1')->from('core_relations as p')
                    ->whereColumn('p.content_type', 'r.content_type')->whereColumn('p.content_id', 'r.content_id')
                    ->where('p.target_type', $type)->where('p.target_id', (int) $id));
            })
            ->whereNotExists(fn (Builder $s) => $s->selectRaw('1')->from('core_relations as s')
                ->whereColumn('s.content_type', 'r.content_type')->whereColumn('s.content_id', 'r.content_id')
                ->where('s.target_type', RelationEngine::SITE_TYPE))
            ->whereNotExists(fn (Builder $d) => $d->selectRaw('1')->from('core_main_site_requests as d')
                ->whereColumn('d.content_type', 'r.content_type')->whereColumn('d.content_id', 'r.content_id')
                ->where('d.decision', MainSiteRequest::DISMISSED))
            ->groupBy('r.content_type', 'r.content_id')
            ->get();

        $pending = MainSiteRequest::query()->with('requester:id,name')->where('decision', MainSiteRequest::PENDING)
            ->whereIn('content_type', array_keys($types))->get()
            ->keyBy(fn (MainSiteRequest $r): string => "{$r->content_type}:{$r->content_id}");

        $items = collect();

        foreach ($rows->groupBy('content_type') as $alias => $group) {
            $model = $types[$alias];
            $records = $model::query()->withoutGlobalScope(UserScopeFilter::class)
                ->whereKey($group->pluck('content_id'))->get()->keyBy(fn (Model $m) => $m->getKey());

            foreach ($group as $row) {
                $record = $records->get($row->content_id);
                $key = "{$alias}:{$row->content_id}";
                $request = $pending->get($key);

                // Old content leaves the period filter, unless someone asked for it
                if ($record === null || ($since && $request === null && $row->shown_at < $since)) {
                    continue;
                }

                $items[$key] = [
                    'key' => $key,
                    'type' => $alias,
                    'type_label' => Str::headline(class_basename($model)),
                    'id' => $record->getKey(),
                    'title' => (string) ($record->getAttribute($model::$titleAttribute ?? 'name') ?? '#'.$record->getKey()),
                    'places' => collect($this->engine->targetsOf($record))->map(fn (string $k): string => Str::after($this->targets->label($k), ': '))->implode(', '),
                    'shown_at' => $row->shown_at,
                    'requested' => $request !== null,
                    'requested_by' => $request?->requester?->name,
                    'url' => $this->editUrl($record),
                ];
            }
        }

        if (filled($search)) {
            $items = $items->filter(fn (array $item): bool => Str::contains($item['title'], $search, ignoreCase: true));
        }

        return $items->sortBy([['requested', 'desc'], ['shown_at', 'desc']]);
    }

    public function pendingCount(?User $user): int
    {
        $types = array_keys($this->types($user));

        return $types === [] ? 0 : MainSiteRequest::query()->where('decision', MainSiteRequest::PENDING)->whereIn('content_type', $types)->count();
    }

    public function publish(string $key, ?User $user): void
    {
        $this->engine->publishToMainSite($this->content($key), $user);
    }

    /** Not for the main site: the request (if any) is closed and the item leaves the queue. */
    public function dismiss(string $key, ?User $user): void
    {
        [$type, $id] = explode(':', $key, 2);

        $updated = MainSiteRequest::query()->where('content_type', $type)->where('content_id', $id)
            ->where('decision', MainSiteRequest::PENDING)
            ->update(['decision' => MainSiteRequest::DISMISSED, 'decided_by' => $user?->id, 'decided_at' => now()]);

        if ($updated === 0) {
            MainSiteRequest::query()->create([
                'content_type' => $type, 'content_id' => (int) $id, 'requested_at' => now(),
                'decision' => MainSiteRequest::DISMISSED, 'decided_by' => $user?->id, 'decided_at' => now(),
            ]);
        }
    }

    public function content(string $key): Model
    {
        [$type, $id] = explode(':', $key, 2);
        $model = Relation::getMorphedModel($type) ?? throw new RelationRejected(["Unknown content type {$type}."]);

        return $model::query()->withoutGlobalScope(UserScopeFilter::class)->findOrFail($id);
    }

    protected function editUrl(Model $record): ?string
    {
        $resource = Filament::getCurrentOrDefaultPanel()?->getModelResource($record);

        return $resource && $resource::hasPage('edit') ? $resource::getUrl('edit', ['record' => $record]) : null;
    }
}
