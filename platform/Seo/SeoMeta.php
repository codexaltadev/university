<?php

namespace Platform\Seo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Platform\Concerns\HasMediaUsage;
use Spatie\Translatable\HasTranslations;

/**
 * SEO panel values of one record (core_seo_meta, P-14, FR-GEN-70). Every field is
 * optional; the website fills the gaps from the record (FR-GEN-71).
 */
class SeoMeta extends Model
{
    use HasMediaUsage;      // og_image
    use HasTranslations;

    protected $table = 'core_seo_meta';

    protected $fillable = ['meta_title', 'meta_description', 'canonical_url', 'noindex', 'schema_overrides'];

    public array $translatable = ['meta_title', 'meta_description'];

    protected function casts(): array
    {
        return ['noindex' => 'boolean', 'schema_overrides' => 'array'];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
