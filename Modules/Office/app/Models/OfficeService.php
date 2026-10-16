<?php

namespace Modules\Office\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSortOrder;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsSearchable;
use Platform\Models\BaseModel;
use Spatie\Translatable\HasTranslations;

/** A service an office gives (FR-OFF-06): "Transcript issue" — what to bring, fee, time, where to apply, who handles it. Media: form. */
class OfficeService extends BaseModel
{
    // people in the role "responsible"
    use HasMediaUsage;
    use HasSortOrder;
    use HasTranslations;
    use IsAssignable;      // form (the application form file)
    use IsSearchable;       // /services is found through the website search

    public const ROLE = 'responsible';

    public static string $titleAttribute = 'title';

    protected $table = 'office_services';

    protected $fillable = ['office_id', 'title', 'description', 'required_documents', 'fee_note', 'fee_amount', 'processing_time', 'apply_url', 'sort_order', 'is_active'];

    public array $translatable = ['title', 'description', 'required_documents', 'fee_note', 'processing_time'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /** /services#service-12 — every office's services on one page (FR-OFF-06). */
    public function url(?string $locale = null): string
    {
        return '/services#service-'.$this->getKey();
    }

    protected function searchBodyAttributes(): array
    {
        return ['description', 'required_documents'];
    }

    protected function casts(): array
    {
        return ['office_id' => 'integer', 'fee_amount' => 'decimal:2'];
    }
}
