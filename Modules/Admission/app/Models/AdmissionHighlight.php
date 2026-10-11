<?php

namespace Modules\Admission\Models;

use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\HasTaxonomy;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * An admission information card (DB-03 §10): a picture, a title and a link — "Online
 * Application", "Tuition Fees for Local Students" … — grouped by category (Notice,
 * Facility, Campus Life). Media: image.
 */
class AdmissionHighlight extends BaseModel
{
    use HasMediaUsage;
    use HasSortOrder;
    use HasTaxonomy;        // admission.highlight-category
    use HasTranslations;

    public const CATEGORY = 'admission.highlight-category';

    public static string $titleAttribute = 'title';

    protected $table = 'admission_highlights';

    protected $fillable = ['title', 'url', 'open_in_new_tab', 'sort_order', 'is_active'];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean'];
    }
}
