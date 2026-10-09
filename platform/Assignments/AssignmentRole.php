<?php

namespace Platform\Assignments;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Standard Role Catalog entry (core_assignment_roles, DB-01 §8.4, FR-REL-11).
 * The key never changes; admins change label, order and active only.
 */
class AssignmentRole extends Model
{
    use HasTranslations;

    protected $table = 'core_assignment_roles';

    protected $guarded = [];

    public array $translatable = ['label'];

    protected function casts(): array
    {
        return ['is_single_holder' => 'boolean', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $role): void {
            if ($role->isDirty(['role_key', 'assignable_type'])) {
                throw new \LogicException('Role keys are fixed; only the label, order and active state can change.');
            }
        });
    }
}
