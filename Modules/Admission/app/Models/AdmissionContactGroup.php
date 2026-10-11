<?php

namespace Modules\Admission\Models;

use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/**
 * A group of admission contacts — "Local Admission", "International Admission" (FR-ADM-10);
 * its people are assignments in the role "contact" (Administration people, or manual ones).
 */
class AdmissionContactGroup extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;

    public const ROLE = 'contact';

    protected $table = 'admission_contact_groups';

    protected $fillable = ['name', 'sort_order', 'is_active'];

    public array $translatable = ['name'];
}
