<?php

namespace Modules\Waiver\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/**
 * A waiver category of one program level (FR-FEE-05): Result Based, Female, Sibling …
 * Choosing it also matches the rules of the categories it includes (DB-03 v1.1:
 * "Female" includes "Result Based"). A diploma category takes the Diploma CGPA (0–4)
 * in place of the HSC result.
 */
class WaiverCategory extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'waiver_categories';

    protected $fillable = ['level_term_id', 'name', 'uses_diploma_result', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['level_term_id' => 'integer', 'uses_diploma_result' => 'boolean'];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'level_term_id');
    }

    public function includes(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'waiver_category_includes', 'category_id', 'included_category_id');
    }

    /** @return list<int> this category and the ones it includes */
    public function matchingIds(): array
    {
        return array_values(array_unique([$this->getKey(), ...$this->includes()->pluck('waiver_categories.id')->map(fn ($id): int => (int) $id)->all()]));
    }
}
