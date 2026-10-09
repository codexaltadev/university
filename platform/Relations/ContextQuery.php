<?php

namespace Platform\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use InvalidArgumentException;
use Platform\Concerns\HasPublishDate;
use Platform\Hierarchy\EntityHierarchy;

/**
 * Website content of one context (SAD Part B §3.3–3.4). Only frontendVisible()
 * records; the main site context ("site" / "site:0") only has content published
 * there by an Admin (BRD Q-26).
 *
 *   app(RelationEngine::class)->for('department.department:12')
 *       ->content(Faq::class)->includeDescendants()->highlightedFirst(6);
 */
class ContextQuery
{
    public const MODES = ['latest', 'highlighted', 'highlighted_first', 'manual'];

    /** @var class-string<Model>|null */
    protected ?string $model = null;

    protected bool $descendants = false;

    public function __construct(protected string $context)
    {
        if ($this->context === RelationEngine::SITE_TYPE) {
            $this->context = RelationEngine::SITE_KEY;
        }
    }

    /** @param class-string<Model> $model */
    public function content(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    /** Also content of child entities, e.g. a department's programs (FR-REL-08). */
    public function includeDescendants(bool $include = true): static
    {
        $this->descendants = $include;

        return $this;
    }

    /** Visible content linked to the context (no order, no limit). */
    public function query(): Builder
    {
        $model = $this->modelInstance();
        $targets = $this->targets();

        return $model::query()
            ->frontendVisible()
            ->whereExists(function (QueryBuilder $sub) use ($model, $targets): void {
                $sub->selectRaw('1')->from('core_relations as cr')
                    ->whereColumn('cr.content_id', $model->getQualifiedKeyName())
                    ->where('cr.content_type', $model->getMorphClass())
                    ->where(function (QueryBuilder $or) use ($targets): void {
                        foreach ($targets as $type => $ids) {
                            $or->orWhere(fn (QueryBuilder $q) => $q->where('cr.target_type', $type)->whereIn('cr.target_id', $ids));
                        }
                    });
            });
    }

    public function latest(int $limit): Collection
    {
        return $this->newestFirst($this->query())->limit($limit)->get();
    }

    /** Highlighted in this context and not expired, in highlight order. */
    public function highlighted(int $limit): Collection
    {
        $ids = ContentRelation::query()
            ->where('content_type', $this->modelInstance()->getMorphClass())
            ->where('target_type', explode(':', $this->context)[0])
            ->where('target_id', (int) explode(':', $this->context)[1])
            ->highlightedNow()
            ->orderBy('highlight_order')
            ->pluck('content_id')
            ->all();

        return $this->inOrder($ids, $limit);
    }

    /** Default homepage mode: highlighted first, then the latest, without duplicates. */
    public function highlightedFirst(int $limit): Collection
    {
        $highlighted = $this->highlighted($limit);

        if ($highlighted->count() >= $limit) {
            return $highlighted;
        }

        $rest = $this->newestFirst($this->query())
            ->whereKeyNot($highlighted->modelKeys())
            ->limit($limit - $highlighted->count())
            ->get();

        return $highlighted->concat($rest)->values();
    }

    /** Hand-picked ids in their order; invisible ones are skipped. */
    public function manual(array $ids, ?int $limit = null): Collection
    {
        return $this->inOrder($ids, $limit ?? count($ids));
    }

    /** Homepage section source mode (FR-HOME-11). */
    public function mode(string $mode, int $limit, array $manualIds = []): Collection
    {
        return match ($mode) {
            'latest' => $this->latest($limit),
            'highlighted' => $this->highlighted($limit),
            'highlighted_first' => $this->highlightedFirst($limit),
            'manual' => $this->manual($manualIds, $limit),
            default => throw new InvalidArgumentException("Unknown source mode \"{$mode}\"."),
        };
    }

    /** @return array<string, list<int>> target type => ids */
    protected function targets(): array
    {
        $keys = [$this->context];

        if ($this->descendants && $this->context !== RelationEngine::SITE_KEY) {
            $keys = app(EntityHierarchy::class)->withDescendants($keys);
        }

        $grouped = [];
        foreach ($keys as $key) {
            [$type, $id] = explode(':', $key, 2);
            $grouped[$type][] = (int) $id;
        }

        return $grouped;
    }

    /** @param list<int|string> $ids */
    protected function inOrder(array $ids, int $limit): Collection
    {
        if ($ids === []) {
            return $this->modelInstance()->newCollection();
        }

        $found = $this->query()->whereKey($ids)->get()->keyBy(fn (Model $m) => $m->getKey());

        return $this->modelInstance()->newCollection(
            collect($ids)->map(fn ($id) => $found->get($id))->filter()->take($limit)->values()->all()
        );
    }

    protected function newestFirst(Builder $query): Builder
    {
        $model = $this->modelInstance();

        if (in_array(HasPublishDate::class, class_uses_recursive($model), true)) {
            $query->orderByDesc($model->qualifyColumn('published_at'));
        }

        return $query->orderByDesc($model->getQualifiedKeyName());
    }

    protected function modelInstance(): Model
    {
        if ($this->model === null) {
            throw new InvalidArgumentException('Call content(Model::class) first.');
        }

        return new $this->model;
    }
}
