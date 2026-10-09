<?php

namespace Modules\Office\Models;

use Platform\Concerns\HasSlug;
use Platform\Concerns\IsAssignable;
use Platform\Concerns\IsRelationTarget;
use Platform\Contracts\HasEntityPermissions;
use Platform\Models\BaseModel;

/**
 * Office access is permission-based ("Manage:Office#12", BRD Q-13/14), not data
 * scope, so it is a relation target and assignable but not Scopeable.
 */
class Office extends BaseModel implements HasEntityPermissions
{
    use HasSlug;            // microsite /office/{slug}
    use IsAssignable;
    use IsRelationTarget;

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
}
