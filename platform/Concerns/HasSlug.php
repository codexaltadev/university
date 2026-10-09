<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Platform\Localization\Languages;
use Platform\Slugs\Slug;

/**
 * Web address per language in core_slugs (FR-GEN-30..32, ADR-11).
 * Generated from $slugSource on create; changing it keeps the old one as
 * history so the old URL can redirect (301) to the new one.
 *
 *     protected string $slugSource = 'name';   // translatable or plain attribute
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::created(function (Model $model): void {
            foreach ($model->slugLocales() as $locale) {
                if ($model->slug($locale) === null) {
                    $model->changeSlug($locale, $model->slugBase($locale));
                }
            }
        });

        $forget = fn (Model $model) => $model->slugs()->delete();
        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function slugs(): MorphMany
    {
        return $this->morphMany(Slug::class, 'sluggable');
    }

    public function slug(?string $locale = null): ?string
    {
        return $this->slugs()
            ->where('locale', $locale ?? app()->getLocale())
            ->where('is_current', true)
            ->value('slug');
    }

    /**
     * Set the web address. The old one stays as history for redirects; a slug used
     * by another record of the same type (current or history) gets a number suffix.
     */
    public function changeSlug(string $locale, string $wanted): string
    {
        $slug = $this->uniqueSlug($locale, Str::slug($wanted) ?: (string) $this->getKey());

        $existing = $this->slugs()->where('locale', $locale)->where('slug', $slug)->first();

        $this->slugs()->where('locale', $locale)->where('is_current', true)->update(['is_current' => false]);

        $existing
            ? $existing->update(['is_current' => true])
            : $this->slugs()->create(['locale' => $locale, 'slug' => $slug, 'is_current' => true]);

        return $slug;
    }

    /**
     * Find a record by a slug in a language.
     *
     * @return array{0: static, 1: bool}|null [record, slug is current]
     */
    public static function findBySlug(string $slug, ?string $locale = null): ?array
    {
        $row = Slug::query()
            ->where('sluggable_type', (new static)->getMorphClass())
            ->where('locale', $locale ?? app()->getLocale())
            ->where('slug', $slug)
            ->first();

        $record = $row ? static::query()->find($row->sluggable_id) : null;

        return $record ? [$record, $row->is_current] : null;
    }

    /** @return list<string> */
    protected function slugLocales(): array
    {
        return app(Languages::class)->codes();
    }

    protected function slugBase(string $locale): string
    {
        $source = $this->slugSource ?? 'name';

        if (method_exists($this, 'getTranslation') && in_array($source, $this->translatable ?? [], true)) {
            return (string) ($this->getTranslation($source, $locale, false) ?: $this->getTranslation($source, app(Languages::class)->default()));
        }

        return (string) $this->getAttribute($source);
    }

    protected function uniqueSlug(string $locale, string $base): string
    {
        $slug = $base;

        for ($n = 2; $this->slugTakenByOther($locale, $slug); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }

    protected function slugTakenByOther(string $locale, string $slug): bool
    {
        return Slug::query()
            ->where('sluggable_type', $this->getMorphClass())
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('sluggable_id', '!=', $this->getKey())
            ->exists();
    }
}
