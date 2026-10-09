<?php

namespace Platform\Modules;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * module.json with its "uwms" block (SAD Part A §5).
 */
final class Manifest
{
    /** @param array<string, mixed> $data full module.json */
    public function __construct(public readonly array $data, public readonly string $path) {}

    public static function fromDirectory(string $directory): self
    {
        $file = rtrim($directory, '/').'/module.json';

        if (! is_file($file)) {
            throw new RuntimeException("module.json not found in {$directory}");
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new RuntimeException("module.json in {$directory} is not valid JSON");
        }

        return new self($data, rtrim($directory, '/'));
    }

    /** nwidart name = folder = PHP namespace segment, e.g. "FacultyMember". */
    public function name(): string
    {
        return (string) ($this->data['name'] ?? '');
    }

    public function slug(): string
    {
        return (string) $this->uwms('slug', '');
    }

    public function version(): string
    {
        return (string) $this->uwms('version', '');
    }

    public function type(): string
    {
        return (string) $this->uwms('type', '');
    }

    public function isCore(): bool
    {
        return $this->type() === 'core';
    }

    public function requiresCore(): string
    {
        return (string) $this->uwms('requires_core', '');
    }

    public function defaultActive(): bool
    {
        return (bool) $this->uwms('default_active', true);
    }

    public function tablePrefix(): string
    {
        return (string) $this->uwms('table_prefix', '');
    }

    /** Prefix the slug must produce: "faculty-member" → "faculty_member_". */
    public function expectedTablePrefix(): string
    {
        return Str::replace('-', '_', $this->slug()).'_';
    }

    /** @return array<string, string> slug => version constraint */
    public function dependencies(): array
    {
        return (array) $this->uwms('dependencies', []);
    }

    /** @return list<string> */
    public function optionalDependencies(): array
    {
        return array_values((array) $this->uwms('optional_dependencies', []));
    }

    /** @return array<string, string> alias => class */
    public function morphAliases(): array
    {
        return (array) $this->uwms('morph_aliases', []);
    }

    /** @return list<string> */
    public function customPermissions(): array
    {
        return array_values((array) $this->uwms('custom_permissions', []));
    }

    /** @return array<string, mixed> */
    public function provides(): array
    {
        return (array) $this->uwms('provides', []);
    }

    public function namespace(): string
    {
        return config('modules.namespace', 'Modules').'\\'.$this->name();
    }

    public function extensionClass(): string
    {
        return $this->namespace().'\\'.$this->name().'Extension';
    }

    public function migrationsPath(): string
    {
        return $this->path.'/database/migrations';
    }

    public function seederClass(): string
    {
        return $this->namespace().'\\Database\\Seeders\\'.$this->name().'DatabaseSeeder';
    }

    public function uwms(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, 'uwms.'.$key, $default);
    }
}
