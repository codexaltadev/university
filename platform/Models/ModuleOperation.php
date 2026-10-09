<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * One install / activate / deactivate / update / uninstall run, step by step
 * (core_module_operations, DB-01 §3.3, FR-MODM-12). Log table.
 */
class ModuleOperation extends Model
{
    public $timestamps = false;

    protected $table = 'core_module_operations';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public static function start(string $slug, string $operation): self
    {
        return self::create([
            'module_slug' => $slug,
            'operation' => $operation,
            'status' => 'running',
            'steps' => [],
            'performed_by' => Auth::id(),
            'started_at' => now(),
        ]);
    }

    public function step(string $step, string $status = 'ok', ?string $message = null): self
    {
        $this->steps = [...$this->steps, ['step' => $step, 'status' => $status, 'message' => $message, 'at' => now()->toIso8601String()]];
        $this->save();

        return $this;
    }

    public function finish(string $status = 'success', ?string $error = null): self
    {
        $this->update(['status' => $status, 'error' => $error, 'finished_at' => now()]);

        return $this;
    }
}
