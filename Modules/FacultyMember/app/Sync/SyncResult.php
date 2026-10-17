<?php

namespace Modules\FacultyMember\Sync;

/** What one sync did (shown above the admin list and printed by the command). */
final class SyncResult
{
    public int $total = 0;

    public int $created = 0;

    public int $updated = 0;

    public int $restored = 0;

    public int $unchanged = 0;

    public int $removed = 0;

    /** @var list<string> department short names the faculty site uses but this site does not have (BRL-FM-04) */
    public array $unmatchedDepartments = [];

    /** @var list<string> "Record 12: no name" */
    public array $errors = [];

    public function __construct(public string $source = '', public ?string $failure = null) {}

    public function ok(): bool
    {
        return $this->failure === null;
    }

    /** @return array<string, int> the numbers, as translation replacements */
    public static function counts(array $stored): array
    {
        return array_map('intval', array_intersect_key($stored, array_flip(['total', 'created', 'updated', 'restored', 'unchanged', 'removed'])));
    }

    /** @return array<string, mixed> stored as Settings faculty-member.last_sync */
    public function toArray(): array
    {
        return [
            'at' => now()->toIso8601String(),
            'source' => $this->source,
            'failure' => $this->failure,
            'total' => $this->total,
            'created' => $this->created,
            'updated' => $this->updated,
            'restored' => $this->restored,
            'unchanged' => $this->unchanged,
            'removed' => $this->removed,
            'unmatched_departments' => array_values(array_slice($this->unmatchedDepartments, 0, 50)),
            'errors' => array_values(array_slice($this->errors, 0, 50)),
        ];
    }
}
