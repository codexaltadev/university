<?php

namespace Modules\Notice\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Notice\Models\Notice;
use Platform\Relations\RelationEngine;
use Platform\Relations\Targets;

/**
 * What the Notice Board shows, ready for a theme (`$page` in notice::index): the places to filter
 * by grouped as DIU does (a faculty with its departments; offices and the rest together), each
 * notice's date box and details, and the addresses of each filter. Themes hold no PHP logic (T-1).
 */
class NoticeBoardPage
{
    /** @var array<string, ?Model> */
    protected array $models = [];

    public function __construct(protected array $data) {}

    public function boardUrl(): string
    {
        return url(($this->data['base'] ?? '').'/'.Notice::PATH);
    }

    /** The board's address with some filters changed (null = removed); the page starts over */
    public function url(array $change = []): string
    {
        $query = array_filter(array_merge([
            'archive' => ($this->data['archive'] ?? false) ? 1 : null,
            'category' => $this->data['category']?->slug(),
            'entity' => $this->data['entity'] ?? null,
            'q' => trim((string) request('q')) ?: null,
            'view' => request('view'),
        ], $change), fn ($v): bool => $v !== null && $v !== '');

        return $this->boardUrl().($query ? '?'.http_build_query($query) : '');
    }

    /** The place chosen in the filter, by name */
    public function entityName(): ?string
    {
        $entity = $this->data['entity'] ?? null;

        return $entity ? ($this->data['entities'][$entity] ?? null) : null;
    }

    /**
     * The places some main-site notice is shown in, as DIU's faculty tabs: each faculty with its
     * departments (and itself), then the other places together.
     *
     * @return list<array{label: string, name: string, current: bool, items: list<array{name: string, short: ?string, url: string, current: bool}>}>
     */
    public function groups(): array
    {
        $groups = [];
        $others = [];
        foreach ($this->data['entities'] ?? [] as $key => $name) {
            $model = $this->model($key);
            $faculty = $model && str_ends_with($model::class, '\\Faculty') ? $model : ($model && str_ends_with($model::class, '\\Department') ? $model->faculty : null);
            $item = ['name' => (string) ($model?->name ?: $name), 'short' => $model?->short_name ? mb_strtoupper((string) $model->short_name) : null, 'url' => $this->url(['entity' => $key]), 'current' => ($this->data['entity'] ?? null) === $key];
            if ($faculty) {
                $id = $faculty->getKey();
                $groups[$id] ??= ['label' => mb_strtoupper((string) ($faculty->short_name ?: $faculty->name)), 'name' => (string) $faculty->name, 'current' => false, 'items' => [], 'sort' => sprintf('%05d', (int) $faculty->sort_order)];
                $groups[$id]['items'][] = $item;
                $groups[$id]['current'] = $groups[$id]['current'] || $item['current'];
            } else {
                $others[] = $item;
            }
        }
        usort($groups, fn (array $a, array $b): int => [$a['sort'], $a['name']] <=> [$b['sort'], $b['name']]);
        $groups = array_map(fn (array $g): array => array_diff_key($g, ['sort' => 1]), $groups);
        if ($others !== []) {
            $groups[] = ['label' => mb_strtoupper(__('notice::notice.page.others')), 'name' => __('notice::notice.page.others'), 'current' => collect($others)->contains('current', true), 'items' => $others];
        }

        return $groups;
    }

    /**
     * @return list<array{title: string, url: string, day: string, month: string, places: string, category: ?string, date: string, views: int, pinned: bool, urgent: bool, new: bool, archived: bool}>
     */
    public function items(): array
    {
        $engine = app(RelationEngine::class);
        $targets = app(Targets::class);

        return collect($this->data['notices']->items())->map(fn (Notice $n): array => [
            'title' => (string) $n->title,
            'url' => url(($this->data['base'] ?? '').'/'.Notice::PATH.'/'.$n->slug()),
            'day' => $n->active_at->format('d'),
            'month' => $n->active_at->translatedFormat('M').','.$n->active_at->format('y'),
            'places' => collect($engine->targetsOf($n))->map(fn (string $key): string => (string) ($this->model($key)?->name ?: $targets->label($key)))->implode(', '),
            'category' => $n->termsIn(Notice::CATEGORY)->first()?->name,
            'date' => $n->active_at->translatedFormat('d M, Y, h:i A'),
            'views' => $n->viewTotal(),
            'pinned' => (bool) $n->is_pinned,
            'urgent' => (bool) $n->is_urgent,
            'new' => $n->isNew(),
            'archived' => ! $n->isOnBoard(),
        ])->all();
    }

    protected function model(string $key): ?Model
    {
        if (! array_key_exists($key, $this->models)) {
            [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
            $class = Relation::getMorphedModel((string) $type);
            $this->models[$key] = $class ? $class::query()->find($id) : null;
        }

        return $this->models[$key];
    }
}
