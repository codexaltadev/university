<?php

namespace Platform\Registry;

/**
 * What active modules registered through their Extension (SAD Part A §7.2).
 * Engines read from here; they never look into module code.
 */
class Registry
{
    /** @var array<string, array<string, list<array<string, mixed>>>> kind => module slug => entries */
    protected array $entries = [];

    /** @param array<string, mixed> $entry */
    public function add(string $kind, string $module, array $entry): void
    {
        $this->entries[$kind][$module][] = $entry;
    }

    /** @return list<array<string, mixed>> all entries of a kind, each with its "module" */
    public function all(string $kind): array
    {
        $result = [];

        foreach ($this->entries[$kind] ?? [] as $module => $entries) {
            foreach ($entries as $entry) {
                $result[] = ['module' => $module, ...$entry];
            }
        }

        return $result;
    }

    /** @return array<string, list<array<string, mixed>>> kind => entries, for one module */
    public function forModule(string $module): array
    {
        $result = [];

        foreach ($this->entries as $kind => $modules) {
            if (isset($modules[$module])) {
                $result[$kind] = $modules[$module];
            }
        }

        return $result;
    }

    public function forgetModule(string $module): void
    {
        foreach ($this->entries as $kind => $modules) {
            unset($this->entries[$kind][$module]);
        }
    }

    public function flush(): void
    {
        $this->entries = [];
    }
}
