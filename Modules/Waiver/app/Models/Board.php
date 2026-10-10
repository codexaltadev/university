<?php

namespace Modules\Waiver\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Platform\Taxonomy\Term;
use Spatie\Translatable\HasTranslations;

/** An education board of one program level (FR-FEE-05): General, Technical, Madrasha … / Public University … */
class Board extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'name';

    protected $table = 'waiver_boards';

    protected $fillable = ['level_term_id', 'name', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['level_term_id' => 'integer'];
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'level_term_id');
    }
}
