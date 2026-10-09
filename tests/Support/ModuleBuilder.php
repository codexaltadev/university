<?php

namespace Tests\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Builds test modules (folder or ZIP) from tests/Fixtures/module-template.
 */
class ModuleBuilder
{
    protected array $vars;

    /** @var array<string, string|null> relative path => content (null = remove) */
    protected array $files = [];

    /** @var array<string, string> extra raw ZIP entries (name => content), may be unsafe on purpose */
    protected array $rawEntries = [];

    protected ?string $rootFolder = null;

    public function __construct(string $name = 'Sample', ?string $slug = null)
    {
        $slug ??= Str::kebab($name);
        $prefix = Str::replace('-', '_', $slug).'_';

        $this->vars = [
            '{{Name}}' => $name,
            '{{lower}}' => Str::lower($name),
            '{{slug}}' => $slug,
            '{{prefix}}' => $prefix,
            '{{table}}' => $prefix.'items',
            '{{version}}' => '1.0.0',
            '{{ResourceName}}' => $name.'ItemResource',
            '{{dependencies}}' => '{}',
            '{{provides}}' => '{}',
        ];
    }

    public static function make(string $name = 'Sample', ?string $slug = null): self
    {
        return new self($name, $slug);
    }

    public function set(string $token, string $value): self
    {
        $this->vars["{{{$token}}}"] = $value;

        return $this;
    }

    /** @param array<string, string> $dependencies */
    public function dependsOn(array $dependencies): self
    {
        return $this->set('dependencies', json_encode((object) $dependencies));
    }

    /** @param array<string, mixed> $provides */
    public function provides(array $provides): self
    {
        return $this->set('provides', json_encode((object) $provides));
    }

    public function file(string $path, ?string $content): self
    {
        $this->files[$path] = $content;

        return $this;
    }

    public function rawZipEntry(string $name, string $content = 'x'): self
    {
        $this->rawEntries[$name] = $content;

        return $this;
    }

    public function rootFolder(string $folder): self
    {
        $this->rootFolder = $folder;

        return $this;
    }

    /** Renders the module into $parent/{Name}; returns the module directory. */
    public function buildDirectory(string $parent): string
    {
        $target = rtrim($parent, '/').'/'.($this->rootFolder ?? $this->vars['{{Name}}']);
        File::deleteDirectory($target);

        $source = base_path('tests/Fixtures/module-template');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            $relative = strtr(Str::after($file->getPathname(), $source.'/'), $this->vars);
            $this->write($target.'/'.$relative, strtr((string) file_get_contents($file->getPathname()), $this->vars));
        }

        foreach ($this->files as $path => $content) {
            $path = $target.'/'.strtr($path, $this->vars);
            $content === null ? File::delete($path) : $this->write($path, strtr($content, $this->vars));
        }

        return $target;
    }

    /** Renders and zips the module; returns the ZIP path. */
    public function buildZip(): string
    {
        $work = storage_path('framework/testing/module-build/'.Str::uuid());
        $directory = $this->buildDirectory($work);
        $zipPath = $work.'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $zip->addFile($file->getPathname(), basename($directory).'/'.Str::after($file->getPathname(), $directory.'/'));
        }

        foreach ($this->rawEntries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        File::deleteDirectory($work);

        return $zipPath;
    }

    protected function write(string $path, string $content): void
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
    }
}
