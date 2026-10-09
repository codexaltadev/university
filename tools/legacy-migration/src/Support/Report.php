<?php

namespace Legacy\Migration\Support;

/**
 * What one step did (rule M-7): rows in the source (COUNT(*)), created, updated,
 * skipped with the reason, and things a person should look at.
 */
class Report
{
    /** @var array<string, array{source: int, created: int, updated: int, skipped: array<string, int>}> */
    public array $tables = [];

    /** @var list<string> */
    public array $review = [];

    /** @var array<string, string> name => content, kept so nothing is lost (M-6) */
    public array $archive = [];

    public function __construct(public readonly string $step, public readonly string $title) {}

    public function source(string $table, int $count): void
    {
        $this->tables[$table] ??= ['source' => 0, 'created' => 0, 'updated' => 0, 'skipped' => []];
        $this->tables[$table]['source'] = $count;
    }

    public function saved(string $table, bool $created): void
    {
        $this->tables[$table] ??= ['source' => 0, 'created' => 0, 'updated' => 0, 'skipped' => []];
        $this->tables[$table][$created ? 'created' : 'updated']++;
    }

    public function skipped(string $table, string $reason): void
    {
        $this->tables[$table] ??= ['source' => 0, 'created' => 0, 'updated' => 0, 'skipped' => []];
        $this->tables[$table]['skipped'][$reason] = ($this->tables[$table]['skipped'][$reason] ?? 0) + 1;
    }

    public function review(string $note): void
    {
        $this->review[] = $note;
    }

    public function archive(string $name, string $content): void
    {
        $this->archive[$name] = $content;
    }

    public function toMarkdown(): string
    {
        $lines = ["# {$this->step} — {$this->title}", '', 'Run: '.now()->toDateTimeString(), '', '| Legacy table | Source | Created | Updated | Skipped |', '|---|---|---|---|---|'];

        foreach ($this->tables as $table => $row) {
            $skipped = collect($row['skipped'])->map(fn (int $n, string $why): string => "{$n} ({$why})")->implode('; ');
            $lines[] = "| `{$table}` | {$row['source']} | {$row['created']} | {$row['updated']} | ".($skipped ?: '0').' |';
        }

        if ($this->review !== []) {
            $lines = [...$lines, '', '## Manual review', '', ...array_map(fn (string $n): string => "- {$n}", $this->review)];
        }

        if ($this->archive !== []) {
            $lines = [...$lines, '', '## Archived (not in the new design)', ''];
            foreach ($this->archive as $name => $content) {
                $lines = [...$lines, "### {$name}", '', '```html', $content, '```', ''];
            }
        }

        return implode("\n", $lines)."\n";
    }
}
