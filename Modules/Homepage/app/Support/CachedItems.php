<?php

namespace Modules\Homepage\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Section items in the cache without PHP objects: the cache only unserializes plain values
 * (config cache.serializable_classes = false), so models become [class, id] and collections
 * plain lists, and are loaded again — in the same order, one query per model class — when
 * read. A model deleted or hidden meanwhile simply drops out.
 */
final class CachedItems
{
    public static function freeze(mixed $value): mixed
    {
        return match (true) {
            $value instanceof Model => ['__model' => $value::class, 'id' => $value->getKey()],
            $value instanceof EloquentCollection => ['__eloquent' => $value->map(fn ($v) => self::freeze($v))->values()->all()],
            $value instanceof Collection => ['__collection' => $value->map(fn ($v) => self::freeze($v))->all()],
            is_array($value) => array_map(fn ($v) => self::freeze($v), $value),
            is_object($value) => null,   // nothing else is cached
            default => $value,
        };
    }

    public static function thaw(mixed $value): mixed
    {
        $models = [];
        self::collect($value, $models);
        $loaded = [];
        foreach ($models as $class => $ids) {
            $loaded[$class] = $class::query()->whereKey(array_values(array_unique($ids)))->get()->keyBy(fn (Model $m) => $m->getKey());
        }

        return self::restore($value, $loaded);
    }

    /** @param array<class-string<Model>, list<int|string>> $models */
    protected static function collect(mixed $value, array &$models): void
    {
        if (! is_array($value)) {
            return;
        }
        if (isset($value['__model'], $value['id']) && count($value) === 2) {
            $models[$value['__model']][] = $value['id'];

            return;
        }
        foreach ($value as $item) {
            self::collect($item, $models);
        }
    }

    /** @param array<class-string<Model>, Collection<int|string, Model>> $loaded */
    protected static function restore(mixed $value, array $loaded): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (isset($value['__model'], $value['id']) && count($value) === 2) {
            return $loaded[$value['__model']]?->get($value['id']);
        }
        if (array_key_exists('__eloquent', $value) && count($value) === 1) {
            return new EloquentCollection(array_values(array_filter(array_map(fn ($v) => self::restore($v, $loaded), $value['__eloquent']))));
        }
        if (array_key_exists('__collection', $value) && count($value) === 1) {
            return collect(array_map(fn ($v) => self::restore($v, $loaded), $value['__collection']));
        }

        return array_map(fn ($v) => self::restore($v, $loaded), $value);
    }
}
