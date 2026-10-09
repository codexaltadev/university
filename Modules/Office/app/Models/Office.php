<?php

namespace Modules\Office\Models;

use Platform\Concerns\HasMediaUsage;
use Platform\Concerns\HasSlug;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Concerns\IsSearchable;
use Platform\Context\MicrositeResolver;
use Platform\Contracts\HasEntityPermissions;
use Platform\Models\BaseModel;

/**
 * Office access is permission-based ("Manage:Office#12", BRD Q-13/14), not data
 * scope, so it is a relation target and assignable but not Scopeable.
 */
class Office extends BaseModel implements HasEntityPermissions
{
    use HasMediaUsage;      // logo (Media Library)
    use HasSlug;            // microsite /office/{slug}
    use IsAssignable;
    use IsRelationTarget;
    use IsSearchable;       // website search: the office's microsite (FR-SRCH-01)

    protected $table = 'office_offices';

    protected $fillable = ['name', 'is_active'];

    public static function entityPermissionSubject(): string
    {
        return 'Office';
    }

    public static function entityPermissionActions(): array
    {
        return ['Manage'];
    }

    public function entityPermissionLabel(): string
    {
        return $this->name;
    }

    /** The office's microsite, e.g. /office/exam-office. */
    public function url(?string $locale = null): ?string
    {
        return app(MicrositeResolver::class)->urlFor($this, $locale);
    }

    /** The office itself: found by "only in this office" too (FR-SRCH-06). */
    public function searchOwnerKey(): ?string
    {
        return $this->getMorphClass().':'.$this->getKey();
    }
}
