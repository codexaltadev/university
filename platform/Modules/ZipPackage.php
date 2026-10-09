<?php

namespace Platform\Modules;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * A module ZIP: checked before anything is extracted (SAD Part A §10.1, steps 1–2).
 * Expected layout: one root folder named after the module, e.g. "Faq/module.json".
 */
class ZipPackage
{
    /** @var list<string> */
    protected array $errors = [];

    protected ?string $root = null;

    public function __construct(protected string $zipPath) {}

    /**
     * Security and structure checks on the archive listing (FR-MODM-03).
     *
     * @return list<string> errors, empty when the package is safe to extract
     */
    public function inspect(): array
    {
        $this->errors = [];

        if (! is_file($this->zipPath)) {
            return ['ZIP file not found.'];
        }

        if (filesize($this->zipPath) > config('platform.modules.max_zip_size')) {
            return ['ZIP is larger than the allowed size.'];
        }

        $zip = new ZipArchive;

        if ($zip->open($this->zipPath) !== true) {
            return ['File is not a valid ZIP archive.'];
        }

        $roots = [];
        $allowed = config('platform.modules.allowed_extensions');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $roots[Str::before($name, '/')] = true;

            if (str_contains($name, '..') || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('/^[A-Za-z]:/', $name)) {
                $this->errors[] = "Unsafe path in ZIP: {$name}";

                continue;
            }

            $zip->getExternalAttributesIndex($i, $os, $attributes);
            if ($os === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                $this->errors[] = "Symbolic links are not allowed: {$name}";
            }

            if (preg_match('#(^|/)(vendor|node_modules|\.git)/#', $name)) {
                $this->errors[] = "Folder not allowed in a module package: {$name}";
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            $basename = basename($name);
            if ($basename === '.env' || str_starts_with($basename, '.env.')) {
                $this->errors[] = "Environment files are not allowed: {$name}";
            }

            $extension = Str::lower(pathinfo($basename, PATHINFO_EXTENSION) ?: ltrim($basename, '.'));
            if (! in_array($extension, $allowed, true)) {
                $this->errors[] = "File type not allowed: {$name}";
            }
        }

        if (count($roots) !== 1) {
            $this->errors[] = 'ZIP must contain exactly one module folder at its root.';
        } else {
            $this->root = array_key_first($roots);

            foreach (['module.json', 'composer.json', 'README.md', "app/{$this->root}Extension.php"] as $required) {
                if ($zip->locateName("{$this->root}/{$required}") === false) {
                    $this->errors[] = "Missing required file: {$this->root}/{$required}";
                }
            }

            $composer = $zip->getFromName("{$this->root}/composer.json");
            if ($composer !== false && ! empty(json_decode($composer, true)['require'] ?? [])) {
                $this->errors[] = 'composer.json must not require packages (ADR-03); ask for them in a core release.';
            }
        }

        $zip->close();

        return $this->errors;
    }

    /** Extracts into a fresh staging folder; returns the module directory. */
    public function extractToStaging(): string
    {
        $staging = rtrim(config('platform.modules.staging_path'), '/').'/'.Str::uuid();
        File::ensureDirectoryExists($staging);

        $zip = new ZipArchive;
        $zip->open($this->zipPath);
        $zip->extractTo($staging);
        $zip->close();

        return $staging.'/'.$this->root;
    }

    public function rootFolder(): ?string
    {
        return $this->root;
    }
}
