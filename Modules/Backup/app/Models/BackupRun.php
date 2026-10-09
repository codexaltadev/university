<?php

namespace Modules\Backup\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;
use Platform\Models\LogModel;

/** One backup attempt (backup_runs, DB-02 §15): type, result, file, size, who started it. */
class BackupRun extends LogModel
{
    public const TYPES = ['database', 'full', 'pre_install'];

    public const RUNNING = 'running';

    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public $timestamps = false;

    protected $table = 'backup_runs';

    protected $fillable = ['type', 'status', 'disk', 'path', 'size_bytes', 'error', 'reason', 'triggered_by', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function size(): ?string
    {
        return $this->size_bytes !== null ? Number::fileSize($this->size_bytes, precision: 1) : null;
    }
}
