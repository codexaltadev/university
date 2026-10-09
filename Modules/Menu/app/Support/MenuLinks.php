<?php

namespace Modules\Menu\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Menu\Models\MenuItem;
use Platform\Context\MicrositeResolver;
use Platform\Media\MediaItem;
use Platform\Models\BaseModel;
use Platform\Registry\Registry;
use Platform\Scope\UserScopeFilter;
use Platform\Support\MorphMap;
use Platform\Taxonomy\Term;
use Platform\Taxonomy\Vocabularies;

/**
 * Where a menu item points, what it is called and whether the website shows it
 * (FR-MENU-03/04/10/11). Record types are the models modules registered with
 * $r->menuItemType(Model::class); a record needs url($locale) or a microsite.
 */
class MenuLinks
{
    /** @return array<string, class-string<Model>> alias => model, of active modules */
    public function recordTypes(): array
    {
        return collect(app(Registry::class)->all('menuItemType'))
            ->mapWithKeys(fn (array $entry): array => [MorphMap::alias($entry['model']) => $entry['model']])
            ->all();
    }

    /** @return array<string, string> alias => "Page" */
    public function recordTypeLabels(): array
    {
        return collect($this->recordTypes())->map(fn (string $model): string => Str::headline(class_basename($model)))->all();
    }

    /**
     * Records of a type to pick from (searchable).
     *
     * @return array<int, string> id => title
     */
    public function recordOptions(?string $alias, ?string $search = null, array $ids = []): array
    {
        $model = $alias ? ($this->recordTypes()[$alias] ?? null) : null;

        if ($model === null) {
            return [];
        }

        $title = $model::$titleAttribute ?? 'name';

        return $model::query()->withoutGlobalScope(UserScopeFilter::class)
            ->when($ids !== [], fn ($q) => $q->whereKey($ids))
            ->when(filled($search), fn ($q) => $q->where($title, 'like', "%{$search}%"))
            ->limit(50)->get()
            ->mapWithKeys(fn (Model $record): array => [$record->getKey() => (string) $record->getAttribute($title)])
            ->all();
    }

    /** @return array<string, string> route name => label, listing pages modules offer (named "site.{module}.index") */
    public function modulePages(): array
    {
        return collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn (string $name): bool => str_starts_with($name, 'site.') && str_ends_with($name, '.index'))
            ->mapWithKeys(fn (string $name): array => [$name => Str::headline(Str::between($name, 'site.', '.index'))])
            ->all();
    }

    public function target(MenuItem $item): ?Model
    {
        if (! $item->linkable_type || ! $item->linkable_id) {
            return null;
        }

        $model = Relation::getMorphedModel($item->linkable_type);

        if ($model === null) {
            return null;
        }

        $query = $model::query()->withoutGlobalScope(UserScopeFilter::class);

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withTrashed();
        }

        return $query->find($item->linkable_id);
    }

    public function href(MenuItem $item, ?string $locale = null): ?string
    {
        return match ($item->type) {
            'url' => $item->url,
            'module_page' => $item->route_key && Route::has($item->route_key) ? route($item->route_key, [], false) : null,
            'file' => ($media = $this->target($item)) instanceof MediaItem ? $media->permalink() : null,
            'taxonomy' => ($term = $this->target($item)) instanceof Term ? $this->termUrl($term, $locale) : null,
            'record' => ($record = $this->target($item)) ? $this->recordUrl($record, $locale) : null,
            default => null,   // heading
        };
    }

    /** The item's own title in this language, else the linked record's. */
    public function label(MenuItem $item, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $own = $item->getTranslation('title', $locale, true);

        if (filled($own)) {
            return $own;
        }

        $target = $this->target($item);

        if ($target === null) {
            return (string) $item->url;
        }

        $attribute = $target::$titleAttribute ?? 'name';

        // In the language asked for, not the request's (Bangla menus show Bangla page titles)
        return method_exists($target, 'isTranslatableAttribute') && $target->isTranslatableAttribute($attribute)
            ? (string) $target->getTranslation($attribute, $locale)
            : (string) $target->getAttribute($attribute);
    }

    /** FR-MENU-10/11: the website hides the item while this returns a reason. */
    public function problem(MenuItem $item): ?string
    {
        if (! $item->is_active) {
            return 'inactive';
        }

        if (in_array($item->type, ['url', 'heading'], true)) {
            return null;
        }

        if ($item->type === 'module_page') {
            return $item->route_key && Route::has($item->route_key) ? null : 'module_off';
        }

        if ($item->type === 'record' && ! isset($this->recordTypes()[$item->linkable_type])) {
            return 'module_off';
        }

        $target = $this->target($item);

        if ($target === null || (method_exists($target, 'trashed') && $target->trashed())) {
            return 'missing';
        }

        if ($target instanceof Term && ! app(Vocabularies::class)->isRegistered($target->vocabulary?->key ?? '')) {
            return 'module_off';
        }

        if ($target instanceof BaseModel && ! $target->isFrontendVisible()) {
            return 'hidden';
        }

        return null;
    }

    public function isVisible(MenuItem $item): bool
    {
        return $this->problem($item) === null;
    }

    protected function recordUrl(Model $record, ?string $locale): ?string
    {
        if (method_exists($record, 'url')) {
            return $record->url($locale);
        }

        return app(MicrositeResolver::class)->urlFor($record, $locale);
    }

    /** Category archive (FR-TAX-05): /{module}/{vocabulary}/{term}, shared tags /tags/{term}. */
    protected function termUrl(Term $term, ?string $locale): string
    {
        $vocabulary = $term->vocabulary;
        $base = $vocabulary->module_slug
            ? '/'.$vocabulary->module_slug.'/'.Str::after($vocabulary->key, '.')
            : '/tags';

        return $base.'/'.$term->slug($locale);
    }
}
