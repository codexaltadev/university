<?php

namespace Modules\Office\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A section / sub-unit of an office (FR-OFF-04): Registrar Office → Student Service …; its people are "team". */
class OfficeSection extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;       // people in the role "team"

    public const ROLE = 'team';

    public static string $titleAttribute = 'name';

    protected $table = 'office_sections';

    protected $fillable = ['office_id', 'name', 'description', 'email', 'phone', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
