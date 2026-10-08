<?php

namespace Modules\Office\Models;

use Illuminate\Database\Eloquent\Model;
use Platform\Contracts\HasEntityPermissions;

class Office extends Model implements HasEntityPermissions
{
    protected $table = 'office_offices';

    protected $fillable = ['name'];

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
