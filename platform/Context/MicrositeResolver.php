<?php

namespace Platform\Context;

use Illuminate\Database\Eloquent\Model;
use Platform\Concerns\HasSlug;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;

/**
 * Public URL → SiteContext (SAD Part B §6.1):
 *
 *   /en/department/cse/news/convocation-2026
 *        │          │   └ rest: news/convocation-2026 (module route)
 *        │          └ slug → core_slugs → department.department:12
 *        └ route prefix of a registered microsite entity
 *
 * An old slug resolves too, with the URL to redirect to (301, FR-GEN-32).
 */
class MicrositeResolver
{
    public function __construct(protected Registry $registry) {}

    /** @return array<string, class-string<Model>> route prefix => model */
    public function prefixes(): array
    {
        return collect($this->registry->all('micrositeEntity'))
            ->mapWithKeys(fn (array $entry): array => [$entry['routePrefix'] => $entry['model']])
            ->all();
    }

    /**
     * @return array{context: SiteContext, rest: string, redirect: ?string}|null
     *                                                                           null = microsite prefix with an unknown slug (404)
     */
    public function resolve(string $path, ?string $locale = null): ?array
    {
        $locale ??= app()->getLocale();
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

        $hasLocale = isset($segments[0]) && in_array($segments[0], config('platform.locales'), true);
        if ($hasLocale) {
            $locale = array_shift($segments);
        }

        $prefixes = $this->prefixes();

        if (! isset($segments[0], $prefixes[$segments[0]])) {
            return ['context' => SiteContext::site(), 'rest' => implode('/', $segments), 'redirect' => null];
        }

        $prefix = array_shift($segments);
        $slug = array_shift($segments);
        $model = $prefixes[$prefix];

        if ($slug === null || ! in_array(HasSlug::class, class_uses_recursive($model), true)) {
            return null;
        }

        $found = $model::findBySlug($slug, $locale);

        // Inactive, scheduled (published_at in the future) or hidden-module entities have no microsite
        if ($found === null || ! ($found[0] instanceof BaseModel ? $found[0]->isFrontendVisible() : $found[0]->is_active)) {
            return null;
        }

        [$entity, $current] = $found;
        $rest = implode('/', $segments);

        $redirect = $current ? null : '/'.implode('/', array_filter([
            $hasLocale ? $locale : null, $prefix, $entity->slug($locale), $rest,
        ], 'strlen'));

        return ['context' => SiteContext::for($entity), 'rest' => $rest, 'redirect' => $redirect];
    }

    /** Public URL of an entity's microsite, e.g. "/department/cse". */
    public function urlFor(Model $entity, ?string $locale = null): ?string
    {
        $prefix = array_search($entity::class, $this->prefixes(), true);

        return $prefix === false ? null : "/{$prefix}/{$entity->slug($locale)}";
    }
}
