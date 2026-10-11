<?php

namespace Modules\Admission\Models;

use Platform\Concerns\HasSortOrder;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** "How to apply" — one step: title, a few words, an icon (FR-ADM-01). */
class AdmissionStep extends BaseModel
{
    use HasSortOrder;
    use HasTranslations;

    public static string $titleAttribute = 'title';

    protected $table = 'admission_steps';

    protected $fillable = ['title', 'description', 'icon', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'description'];
}
