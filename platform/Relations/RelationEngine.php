<?php

namespace Platform\Relations;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Platform\Events\ContentHighlightChanged;
use Platform\Events\ContentRelationsChanged;
use Platform\Registry\Registry;
use Platform\Scope\ScopeResolver;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;

/**
 * Content Relation Engine (SAD Part B §3, C-13): "Show in" targets, main-site
 * publishing (site:0, Admin only — BRD Q-26) and per-context highlights.
 */
class RelationEngine
{
    public const SITE_TYPE = 'site';

    public const SITE_KEY = 'site:0';

    public function __construct(
        protected ScopeResolver $scope,
        protected Registry $registry,
    ) {}

    // ── Write path (§3.2) ─────────────────────────────────────────────────────

    /**
     * Save a content's targets, main-site state and highlights in one go.
     *
     * @param  list<string>  $targets  entity keys, e.g. ["department.department:12"]
     * @param  bool|null  $mainSite  null = leave as it is
     * @param  array<string, array{order?: int, until?: mixed}|false>  $highlights  context key => highlight, false = remove;
     *                                                                              contexts not listed keep their state
     *
     * @throws RelationRejected
     */
    public function sync(Model $content, array $targets, ?bool $mainSite = null, array $highlights = [], ?Authenticatable $user = null): void
    {
        $user ??= Auth::user();
        $targets = array_values(array_unique($targets));
        $this->validateTargets($targets, $user);

        $type = $content->getMorphClass();
        $id = $content->getKey();
        $existing = ContentRelation::query()->where('content_type', $type)->where('content_id', $id)->get()->keyBy(fn ($r) => $r->targetKey());

        // Main site: only with PublishMain; otherwise the Admin's decision stays untouched (§3.2 step 2)
        $wantSite = $existing->has(self::SITE_KEY);
        if ($mainSite !== null && $this->can($user, 'PublishMain', $content)) {
            $wantSite = $mainSite;
        }

        $wanted = $wantSite ? [...$targets, self::SITE_KEY] : $targets;
        $relationsChanged = false;
        $highlightChanged = false;

        DB::transaction(function () use ($existing, $wanted, $type, $id, $user, $highlights, $content, &$relationsChanged, &$highlightChanged): void {
            foreach ($existing as $key => $relation) {
                if (! in_array($key, $wanted, true)) {
                    $highlightChanged = $highlightChanged || $relation->isHighlightedNow();
                    $relation->delete();
                    $relationsChanged = true;
                }
            }

            foreach ($wanted as $key) {
                [$targetType, $targetId] = explode(':', $key, 2);
                $relation = $existing->get($key) ?? new ContentRelation([
                    'content_type' => $type, 'content_id' => $id,
                    'target_type' => $targetType, 'target_id' => (int) $targetId,
                    'created_by' => $user?->getAuthIdentifier(),
                ]);
                $relationsChanged = $relationsChanged || ! $relation->exists;

                if (array_key_exists($key, $highlights) && $this->mayHighlight($user, $content, $key)) {
                    $highlight = $highlights[$key];
                    $relation->fill($highlight === false
                        ? ['is_highlighted' => false, 'highlight_order' => 0, 'highlight_until' => null]
                        : ['is_highlighted' => true, 'highlight_order' => (int) ($highlight['order'] ?? 0), 'highlight_until' => isset($highlight['until']) ? Carbon::parse($highlight['until']) : null]);
                    $highlightChanged = $highlightChanged || $relation->isDirty(['is_highlighted', 'highlight_order', 'highlight_until']);
                }

                $relation->save();
            }
        });

        if ($wantSite) {
            $this->closeRequests($content, MainSiteRequest::PUBLISHED, $user);
        }

        if ($relationsChanged) {
            ContentRelationsChanged::dispatch($content);
        }
        if ($highlightChanged) {
            ContentHighlightChanged::dispatch($content);
        }
    }

    /** Add targets, keeping the existing ones. */
    public function attach(Model $content, array $targets, ?Authenticatable $user = null): void
    {
        $this->sync($content, [...$this->targetsOf($content), ...$targets], user: $user);
    }

    // ── Main site (Q-26, FR-CNT-H07/H09) ──────────────────────────────────────

    public function publishToMainSite(Model $content, ?Authenticatable $user = null): void
    {
        $user ??= Auth::user();

        if (! $this->can($user, 'PublishMain', $content)) {
            throw new RelationRejected(['Only users with PublishMain permission decide what appears on the main site.']);
        }

        $this->sync($content, $this->targetsOf($content), mainSite: true, user: $user);
    }

    public function removeFromMainSite(Model $content, ?Authenticatable $user = null): void
    {
        $user ??= Auth::user();

        if (! $this->can($user, 'PublishMain', $content)) {
            throw new RelationRejected(['Only users with PublishMain permission decide what appears on the main site.']);
        }

        $this->sync($content, $this->targetsOf($content), mainSite: false, user: $user);
    }

    /** An entity user asks the Admin to show this content on the main site. */
    public function requestMainSite(Model $content, ?Authenticatable $user = null): MainSiteRequest
    {
        $user ??= Auth::user();

        if ($this->isOnMainSite($content)) {
            throw new RelationRejected(['This content is already on the main site.']);
        }

        return MainSiteRequest::query()->firstOrCreate(
            ['content_type' => $content->getMorphClass(), 'content_id' => $content->getKey(), 'decision' => MainSiteRequest::PENDING],
            ['requested_by' => $user?->getAuthIdentifier(), 'requested_at' => now()],
        );
    }

    public function dismissRequest(MainSiteRequest $request, ?Authenticatable $user = null): void
    {
        $request->update(['decision' => MainSiteRequest::DISMISSED, 'decided_by' => ($user ?? Auth::user())?->getAuthIdentifier(), 'decided_at' => now()]);
    }

    public function isOnMainSite(Model $content): bool
    {
        return $this->relationsOf($content)->where('target_type', self::SITE_TYPE)->exists();
    }

    // ── Reading ──────────────────────────────────────────────────────────────

    /** @return list<string> target keys, without the main site */
    public function targetsOf(Model $content): array
    {
        return $this->relationsOf($content)
            ->where('target_type', '!=', self::SITE_TYPE)
            ->get()
            ->map(fn (ContentRelation $r): string => $r->targetKey())
            ->all();
    }

    /** Content of one context (§3.3): RelationEngine::for('department.department:12')->content(Faq::class)->latest(6). */
    public function for(string $contextKey): ContextQuery
    {
        return new ContextQuery($contextKey);
    }

    // ── Housekeeping ─────────────────────────────────────────────────────────

    /** Hourly job (§3.5): switch off expired highlights. */
    public function expireHighlights(): int
    {
        $expired = ContentRelation::query()
            ->where('is_highlighted', true)
            ->whereNotNull('highlight_until')
            ->where('highlight_until', '<=', now())
            ->get();

        foreach ($expired as $relation) {
            $relation->update(['is_highlighted' => false]);
        }

        if ($expired->isNotEmpty()) {
            ContentHighlightChanged::dispatch(null);
        }

        return $expired->count();
    }

    /** Content permanently deleted: remove its rows (no FK on polymorphic references). */
    public function forgetContent(Model $content): void
    {
        $this->relationsOf($content)->delete();
        MainSiteRequest::query()->where('content_type', $content->getMorphClass())->where('content_id', $content->getKey())->delete();
    }

    /** Target permanently deleted: remove relations pointing at it. */
    public function forgetTarget(Model $target): void
    {
        ContentRelation::query()->where('target_type', $target->getMorphClass())->where('target_id', $target->getKey())->delete();
    }

    /** Module uninstalled with its data: remove its rows from kernel tables. */
    public function forgetTypes(array $types): void
    {
        ContentRelation::query()->whereIn('content_type', $types)->orWhereIn('target_type', $types)->delete();
        MainSiteRequest::query()->whereIn('content_type', $types)->delete();
    }

    // ── Rules ────────────────────────────────────────────────────────────────

    /**
     * Targets must be registered relation targets that exist; a scoped user needs
     * at least one and may only use targets inside the scope (FR-GEN-51).
     *
     * @param  list<string>  $targets
     */
    protected function validateTargets(array $targets, ?Authenticatable $user): void
    {
        $errors = [];
        $models = collect($this->registry->all('relationTarget'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']]);

        foreach ($targets as $key) {
            [$type, $id] = array_pad(explode(':', $key, 2), 2, null);

            if ($type === self::SITE_TYPE) {
                $errors[] = 'Use the main-site switch, not a target, for the main site.';
            } elseif (! $models->has($type)) {
                $errors[] = "\"{$type}\" is not something content can be shown in.";
            } elseif (! $models[$type]::query()->withoutGlobalScope(UserScopeFilter::class)->whereKey($id)->exists()) {
                $errors[] = "{$key} does not exist.";
            }
        }

        if ($user && $this->scope->isScoped($user) && ! $this->isSuperAdmin($user)) {
            $allowed = $this->scope->keysFor($user);

            if ($targets === []) {
                $errors[] = 'Choose at least one place to show this in.';
            }

            foreach (array_diff($targets, $allowed) as $outside) {
                $errors[] = "{$outside} is outside your scope.";
            }
        }

        if ($errors !== []) {
            throw new RelationRejected($errors);
        }
    }

    protected function mayHighlight(?Authenticatable $user, Model $content, string $context): bool
    {
        if ($context === self::SITE_KEY) {
            return $this->can($user, 'HighlightMain', $content);
        }

        $keys = $user ? $this->scope->keysFor($user) : null;

        return $keys === null || $this->isSuperAdmin($user) || in_array($context, $keys, true);
    }

    protected function can(?Authenticatable $user, string $action, Model $content): bool
    {
        if ($user === null) {
            return true;   // system / migration
        }

        return $this->isSuperAdmin($user) || $user->can($action.':'.class_basename($content));
    }

    protected function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole(config('filament-shield.super_admin.name'));
    }

    protected function relationsOf(Model $content)
    {
        return ContentRelation::query()->where('content_type', $content->getMorphClass())->where('content_id', $content->getKey());
    }

    protected function closeRequests(Model $content, string $decision, ?Authenticatable $user): void
    {
        MainSiteRequest::query()
            ->where('content_type', $content->getMorphClass())
            ->where('content_id', $content->getKey())
            ->where('decision', MainSiteRequest::PENDING)
            ->update(['decision' => $decision, 'decided_by' => $user?->getAuthIdentifier(), 'decided_at' => now()]);
    }
}
