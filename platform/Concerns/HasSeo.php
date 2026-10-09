<?php

namespace Platform\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;
use Platform\Media\MediaItem;
use Platform\Seo\SeoMeta;

/**
 * SEO panel of a record shown on the website (FR-GEN-70/71, P-14). Empty fields
 * come from the record: title ($titleAttribute), description (excerpt, else the
 * start of the text), picture (featured_image). Override seoDefaults() or
 * jsonLd() for more.
 */
trait HasSeo
{
    public static function bootHasSeo(): void
    {
        $forget = fn (Model $model) => $model->seoMeta()->first()?->delete();
        method_exists(static::class, 'forceDeleted') ? static::forceDeleted($forget) : static::deleted($forget);
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    /**
     * What the website prints for this record, in one language.
     *
     * @return array{title: ?string, description: ?string, image: ?MediaItem, canonical: ?string, noindex: bool, jsonld: array<string, mixed>}
     */
    public function seo(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $meta = $this->relationLoaded('seoMeta') ? $this->seoMeta : $this->seoMeta()->first();
        $auto = $this->seoDefaults($locale);

        $title = $meta?->getTranslation('meta_title', $locale, true);
        $description = $meta?->getTranslation('meta_description', $locale, true);

        return [
            'title' => filled($title) ? $title : $auto['title'],
            'description' => filled($description) ? $description : $auto['description'],
            'image' => $meta?->firstMedia('og_image') ?? $auto['image'],
            'canonical' => $meta?->canonical_url ?: null,
            'noindex' => (bool) $meta?->noindex,
            'jsonld' => array_replace_recursive($this->jsonLd($locale), (array) ($meta?->schema_overrides ?? [])),
        ];
    }

    /** @return array{title: ?string, description: ?string, image: ?MediaItem} FR-GEN-71 */
    protected function seoDefaults(string $locale): array
    {
        $titleAttribute = static::$titleAttribute ?? 'title';
        $text = fn (string $attribute): ?string => $this->hasTranslatableText($attribute)
            ? ($this->getTranslation($attribute, $locale, true) ?: null)
            : ($this->getAttribute($attribute) ?: null);

        $description = $text('excerpt') ?? $text('summary') ?? $text('description');

        return [
            'title' => $text($titleAttribute),
            'description' => $description ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($description))), 160) : null,
            'image' => method_exists($this, 'firstMedia') ? $this->firstMedia('featured_image') : null,
        ];
    }

    /**
     * Structured data (FR-SEO-08, NFR-SEO-03); the SEO panel's schema_overrides go on top.
     *
     * @return array<string, mixed>
     */
    protected function jsonLd(string $locale): array
    {
        return [];
    }

    protected function hasTranslatableText(string $attribute): bool
    {
        return method_exists($this, 'getTranslation') && in_array($attribute, $this->translatable ?? [], true);
    }
}
