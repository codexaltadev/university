<?php

namespace Platform\Theme;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * A theme ZIP, checked before anything is extracted (FR-THEME-02, Theme Guide §14):
 * one root folder with theme.json, safe paths, no symlinks, only view / asset /
 * text file types, no node_modules, .git or .env.
 */
class ThemePackage
{
    public const ALLOWED_EXTENSIONS = [
        'php', 'json', 'md', 'txt', 'css', 'js', 'mjs', 'map', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif',
        'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot', 'gitkeep',
    ];

    public const MAX_SIZE = 30 * 1024 * 1024;

    protected ?string $root = null;

    public function __construct(protected string $zipPath) {}

    /** @return list<string> errors, empty when the archive is safe to extract */
    public function inspect(): array
    {
        if (! is_file($this->zipPath)) {
            return ['ZIP file not found.'];
        }

        if (filesize($this->zipPath) > self::MAX_SIZE) {
            return ['ZIP is larger than the allowed size.'];
        }

        $zip = new ZipArchive;

        if ($zip->open($this->zipPath) !== true) {
            return ['File is not a valid ZIP archive.'];
        }

        $errors = [];
        $roots = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $roots[Str::before($name, '/')] = true;

            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('/^[A-Za-z]:/', $name)) {
                $errors[] = "Unsafe path in ZIP: {$name}";

                continue;
            }

            $zip->getExternalAttributesIndex($i, $os, $attributes);
            if ($os === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                $errors[] = "Symbolic links are not allowed: {$name}";
            }

            if (preg_match('#(^|/)(node_modules|vendor|\.git)/#', $name)) {
                $errors[] = "Folder not allowed in a theme package: {$name}";
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            $basename = basename($name);
            if ($basename === '.env' || str_starts_with($basename, '.env.')) {
                $errors[] = "Environment files are not allowed: {$name}";
            }

            $extension = Str::lower(pathinfo($basename, PATHINFO_EXTENSION) ?: ltrim($basename, '.'));
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = "File type not allowed: {$name}";
            }
        }

        if (count($roots) !== 1) {
            $errors[] = 'ZIP must contain exactly one theme folder at its root.';
        } else {
            $this->root = (string) array_key_first($roots);

            if ($zip->locateName("{$this->root}/theme.json") === false) {
                $errors[] = "Missing required file: {$this->root}/theme.json";
            }
        }

        $zip->close();

        return $errors;
    }

    /** Extracts into a fresh staging folder; returns the theme directory. */
    public function extractToStaging(): string
    {
        $staging = storage_path('app/theme-packages/'.Str::uuid());
        File::ensureDirectoryExists($staging);

        $zip = new ZipArchive;
        $zip->open($this->zipPath);
        $zip->extractTo($staging);
        $zip->close();

        return "{$staging}/{$this->root}";
    }
}
