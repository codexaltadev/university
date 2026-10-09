<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Installed module and its status (core_modules, DB-01 §3.1).
 * System table: no soft delete, no is_active.
 */
class CoreModule extends Model
{
    public const STATUS_INSTALLED = 'installed';   // installed, inactive

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNINSTALLED = 'uninstalled';   // bundled module removed with its data

    protected $table = 'core_modules';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'manifest' => 'array',
            'is_bundled' => 'boolean',
            'installed_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ModuleVersion::class, 'module_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
