<?php

namespace Platform\Theme;

use Illuminate\Database\Eloquent\Model;

/**
 * An installed website theme (theme_themes, DB-02 §13). Only one is active; the
 * bundled default can never be uninstalled (BRL-THEME-01).
 *
 * @property string $slug
 * @property string $name
 * @property string $version
 * @property string $source_path
 * @property array<string, mixed> $manifest
 * @property bool $is_active
 * @property bool $is_bundled
 */
class Theme extends Model
{
    protected $table = 'theme_themes';

    protected $fillable = ['slug', 'name', 'version', 'source_path', 'manifest', 'is_active', 'is_bundled', 'installed_by'];

    protected function casts(): array
    {
        return ['manifest' => 'array', 'is_active' => 'boolean', 'is_bundled' => 'boolean'];
    }

    public function manifest(): ThemeManifest
    {
        return new ThemeManifest($this->manifest, $this->path());
    }

    /** Absolute folder; bundled themes are stored relative to the app. */
    public function path(): string
    {
        return str_starts_with($this->source_path, '/') ? $this->source_path : base_path($this->source_path);
    }
}
