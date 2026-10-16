<?php

namespace Modules\Office\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** "Who to contact" (FR-OFF-07): a purpose — "ID card problem after admission" — its people and where to find them. */
class OfficeSupportContact extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;       // people in the role "contact"

    public const ROLE = 'contact';

    public static string $titleAttribute = 'purpose';

    protected $table = 'office_support_contacts';

    protected $fillable = ['office_id', 'purpose', 'location', 'sort_order', 'is_active'];

    public array $translatable = ['purpose', 'location'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}
