<?php

namespace Platform\Context;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Platform\Concerns\HasSlug;
use Platform\Hierarchy\EntityHierarchy;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;

/**
 * Public URL → SiteContext (SAD Part B §6.1 v0.2, SRS FR-GEN-34 to 36). Microsite URLs follow
 * the hierarchy:
 *
 *   /faculties/fsit                 Faculty — a top entity at "{routePrefix}/{slug}"
 *   /fsit/cse                       Department — under its parent's short base "/{slug}"
 *   /fsit/cse/bsc-in-cse            Program — under its Department
 *   /fsit/cse/lab/robotics          Lab — under its Department after its childSegment "lab"
 *   /lab/central                    the same Lab without a Department: "{routePrefix}/{slug}"
 *   /office/registrar               Office (no parent)
 *   /en/fsit/cse/news/convocation   → context CSE, rest "news/convocation"
 *
 * Read left to right: a route prefix, else a top entity's slug (only entities that have
 * children registered under them), then their children. Whatever does not match is the rest.
 * The URL is compared with the entity's canonical one: an old slug, an entity moved to
 * another parent, "/fsit" alone, or DIU's "/department/cse/…" all answer a 301 to it.
 */
class MicrositeResolver
{
    public function __construct(protected Registry $registry) {}

    /** @return array<string, array{model: class-string<Model>, routePrefix: string, parent: ?string, childSegment: ?string}> by model */
    public function entities(): array
    {
        return collect($this->registry->all('micrositeEntity'))
            ->mapWithKeys(fn (array $e): array => [$e['model'] => ['model' => $e['model'], 'routePrefix' => $e['routePrefix'], 'parent' => $e['parent'] ?? null, 'childSegment' => $e['childSegment'] ?? null]])
            ->all();
    }

    /** @return array<string, class-string<Model>> route prefix => model */
    public function prefixes(): array
    {
        return collect($this->entities())->mapWithKeys(fn (array $e): array => [$e['routePrefix'] => $e['model']])->all();
    }

    /** @return list<class-string<Model>> models registered under $model */
    public function childrenOf(string $model): array
    {
        return collect($this->entities())->filter(fn (array $e): bool => $e['parent'] === $model)->keys()->values()->all();
    }

    /** @return list<class-string<Model>> top entities whose slug opens their children's URLs ("/fsit/…") */
    public function roots(): array
    {
        return collect($this->entities())->filter(fn (array $e): bool => $e['parent'] === null && $this->childrenOf($e['model']) !== [])->keys()->values()->all();
    }

    /**
     * @return array{context: SiteContext, rest: string, redirect: ?string}|null
     *                                                                           null = a microsite path whose entity is unknown or not visible (404)
     */
    public function resolve(string $path, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

        $hasLocale = isset($segments[0]) && in_array($segments[0], config('platform.locales'), true);
        if ($hasLocale) {
            $locale = array_shift($segments);
        }

        $site = ['context' => SiteContext::site(), 'rest' => implode('/', $segments), 'redirect' => null];
        $prefixes = $this->prefixes();
        $entity = null;
        $i = 0;

        if (isset($segments[0], $prefixes[$segments[0]])) {
            if (! isset($segments[1])) {
                return $site;   // "/faculties": the listing
            }
            $entity = $this->find($prefixes[$segments[0]], $segments[1], $locale);
            if ($entity === null) {
                return null;
            }
            $i = 2;
        } elseif (isset($segments[0])) {
            foreach ($this->roots() as $model) {
                if ($entity = $this->find($model, $segments[0], $locale)) {
                    $i = 1;
                    break;
                }
            }
            if ($entity === null) {
                return $site;   // a main site path
            }
        } else {
            return $site;
        }

        // Down the children: "cse", then "bsc-in-cse" or "lab/robotics" (also DIU's "program/…")
        while (isset($segments[$i]) && ($next = $this->child($entity, $segments, $i, $locale))) {
            [$entity, $i] = $next;
        }

        foreach ([$entity, ...$this->ancestors($entity)] as $node) {
            // Inactive, scheduled (published_at in the future) or hidden-module entities have no microsite
            if (! ($node instanceof BaseModel ? $node->isFrontendVisible() : (bool) $node->is_active)) {
                return null;
            }
            // An entity may have no microsite of its own (an office shown as a contact card only, FR-OFF-11)
            if (method_exists($node, 'hasMicrosite') && ! $node->hasMicrosite()) {
                return null;
            }
        }

        $rest = implode('/', array_slice($segments, $i));
        $canonical = $this->urlFor($entity, $locale);
        $asked = '/'.implode('/', array_slice($segments, 0, $i));
        $redirect = $canonical !== null && $canonical !== $asked
            ? '/'.implode('/', array_filter([$hasLocale ? $locale : null, trim($canonical, '/'), $rest], 'strlen'))
            : null;

        return ['context' => SiteContext::for($entity), 'rest' => $rest, 'redirect' => $redirect];
    }

    /**
     * Public URL of an entity's microsite: "/faculties/fsit", "/fsit/cse", "/fsit/cse/lab/robotics".
     */
    public function urlFor(Model $entity, ?string $locale = null): ?string
    {
        $entry = $this->entities()[$entity::class] ?? null;
        if ($entry === null || ! method_exists($entity, 'slug')) {
            return null;
        }

        $parent = $entry['parent'] ? $this->parentOf($entity) : null;

        if ($parent === null) {
            return "/{$entry['routePrefix']}/{$entity->slug($locale)}";
        }

        $base = $this->basePathFor($parent, $locale);

        return $base === null ? null : $base.($entry['childSegment'] ? "/{$entry['childSegment']}" : '').'/'.$entity->slug($locale);
    }

    /** Where an entity's children start: a top entity's own slug ("/fsit"), else its URL. */
    public function basePathFor(Model $entity, ?string $locale = null): ?string
    {
        $entry = $this->entities()[$entity::class] ?? null;

        if ($entry !== null && $entry['parent'] === null && method_exists($entity, 'slug')) {
            return '/'.$entity->slug($locale);
        }

        return $this->urlFor($entity, $locale);
    }

    /** The parent entity (from the hierarchy), or null. */
    public function parentOf(Model $entity): ?Model
    {
        $key = app(EntityHierarchy::class)->parent(EntityHierarchy::key($entity->getMorphClass(), $entity->getKey()));
        if ($key === null) {
            return null;
        }

        [$type, $id] = explode(':', $key, 2);
        $model = Relation::getMorphedModel($type);

        return $model ? UserScopeFilter::withoutScope(fn () => $model::query()->find($id)) : null;
    }

    /** @return list<Model> nearest first */
    public function ancestors(Model $entity): array
    {
        $chain = [];
        for ($node = $this->parentOf($entity); $node !== null && count($chain) < 10; $node = $this->parentOf($node)) {
            $chain[] = $node;
        }

        return $chain;
    }

    /**
     * The child of $entity named at $segments[$i]; one under this parent wins, else one with
     * that slug elsewhere (moved: the URL then redirects to where it is now).
     *
     * @return array{0: Model, 1: int}|null the child and the next index
     */
    protected function child(Model $entity, array $segments, int $i, string $locale): ?array
    {
        $parentKey = EntityHierarchy::key($entity->getMorphClass(), $entity->getKey());
        $candidates = [];

        foreach ($this->childrenOf($entity::class) as $model) {
            $entry = $this->entities()[$model];
            $forms = $entry['childSegment'] ? [[$entry['childSegment'], 2]] : [[null, 1]];
            if ($entry['routePrefix'] !== $entry['childSegment']) {
                $forms[] = [$entry['routePrefix'], 2];   // DIU's "/department/bba/program/…"
            }

            foreach ($forms as [$word, $length]) {
                if ($word !== null && ($segments[$i] !== $word || ! isset($segments[$i + 1]))) {
                    continue;
                }
                if ($found = $this->find($model, $segments[$i + $length - 1], $locale)) {
                    $candidates[] = [$found, $i + $length];
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (app(EntityHierarchy::class)->parent(EntityHierarchy::key($candidate[0]->getMorphClass(), $candidate[0]->getKey())) === $parentKey) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
    }

    protected function find(string $model, string $slug, string $locale): ?Model
    {
        if (! in_array(HasSlug::class, class_uses_recursive($model), true)) {
            return null;
        }

        return UserScopeFilter::withoutScope(fn () => $model::findBySlug($slug, $locale))[0] ?? null;
    }
}
