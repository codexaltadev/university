<?php

namespace Platform\Notifications;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Platform\Models\BaseModel;
use Platform\Scope\UserScopeFilter;

/**
 * How a module's content belongs to an entity and when it counts as updated, for
 * the Content Update Reminder (FR-NOTI-10). Registered with
 * $r->freshnessSource(NoticeFreshness::class); only registered modules can have
 * reminder rules.
 *
 *     class FaqFreshness extends FreshnessSource
 *     {
 *         public static function module(): string { return 'faq'; }
 *         public static function model(): string { return Faq::class; }   // "Show in" (relation), updated_at
 *     }
 */
abstract class FreshnessSource
{
    /** Module slug, stored on the rule. */
    abstract public static function module(): string;

    /** @return class-string<Model> */
    abstract public static function model(): string;

    public static function label(): string
    {
        return Str::headline(static::module());
    }

    /** "relation" (Content Relation "Show in") or "owner" (HasOwner, e.g. pages). */
    public static function link(): string
    {
        return 'relation';
    }

    /** "updated_at" or "published_at". */
    public static function timestamp(): string
    {
        return 'updated_at';
    }

    /** Who looks after this content in an entity (with the entity in scope). */
    public static function permission(): string
    {
        return 'Update:'.class_basename(static::model());
    }

    /** The entity's visible content of this module. */
    public function query(Model $entity): Builder
    {
        $model = static::model();
        $key = $entity->getMorphClass().':'.$entity->getKey();
        $query = $model::query()->withoutGlobalScope(UserScopeFilter::class);

        if (is_subclass_of($model, BaseModel::class)) {
            $query->frontendVisible();
        }

        return static::link() === 'owner' ? $query->ownedBy($key) : $query->shownIn([$key]);
    }

    public function latestFor(Model $entity): ?Carbon
    {
        $latest = $this->query($entity)->max(static::timestamp());

        return $latest ? Carbon::parse($latest) : null;
    }

    public function count(Model $entity): int
    {
        return $this->query($entity)->count();
    }

    /** @return Collection<int, Model> records not updated since $cutoff (Each-record mode, AC-NOTI-03) */
    public function staleRecords(Model $entity, Carbon $cutoff): Collection
    {
        return $this->query($entity)->where(static::timestamp(), '<', $cutoff)->get();
    }

    public function title(Model $record): string
    {
        $attribute = $record::$titleAttribute ?? 'title';
        $value = method_exists($record, 'getTranslation') && in_array($attribute, $record->translatable ?? [], true)
            ? $record->getTranslation($attribute, app()->getLocale())
            : $record->getAttribute($attribute);

        return (string) $value;
    }

    /** Admin edit link of a record (shown in the reminder). */
    public function editUrl(Model $record): ?string
    {
        return null;
    }
}
