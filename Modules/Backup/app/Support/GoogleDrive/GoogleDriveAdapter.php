<?php

namespace Modules\Backup\Support\GoogleDrive;

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\Visibility;

/**
 * Backups on Google Drive (FR-BKP-04): paths ("uwms-backups/database-….zip") map
 * to folders inside one root folder of the connected account (created on first
 * use). Enough of Flysystem for spatie/laravel-backup: write, list, size, date,
 * read (download) and delete.
 */
class GoogleDriveAdapter implements FilesystemAdapter
{
    /** @var array<string, string> folder path => id, per run */
    protected array $folders = [];

    public function __construct(protected GoogleDriveClient $client, protected string $rootFolder = 'University Backups') {}

    public function fileExists(string $path): bool
    {
        return $this->file($path) !== null;
    }

    public function directoryExists(string $path): bool
    {
        return $this->folderId($path, create: false) !== null;
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $contents);
        rewind($stream);
        $this->writeStream($path, $stream, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        [$dir, $name] = $this->split($path);
        $parent = $this->folderId($dir, create: true);

        if ($existing = $this->client->find($name, $parent, folder: false)) {
            $this->client->delete($existing['id']);   // replace
        }

        $this->client->upload($name, $parent, $contents);
    }

    public function read(string $path): string
    {
        return stream_get_contents($this->readStream($path));
    }

    public function readStream(string $path)
    {
        $file = $this->file($path) ?? throw UnableToReadFile::fromLocation($path, 'Not found on Google Drive.');

        return $this->client->download($file['id']);
    }

    public function delete(string $path): void
    {
        if ($file = $this->file($path)) {
            $this->client->delete($file['id']);
        }
    }

    public function deleteDirectory(string $path): void
    {
        if ($id = $this->folderId($path, create: false)) {
            $this->client->delete($id);
            $this->folders = [];
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->folderId($path, create: true);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw UnableToSetVisibility::atLocation($path, 'Google Drive backups are always private.');
    }

    public function visibility(string $path): FileAttributes
    {
        return new FileAttributes($path, null, Visibility::PRIVATE);
    }

    public function mimeType(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        return $this->attributes($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $id = $this->folderId($path, create: false);

        if ($id === null) {
            return;
        }

        foreach ($this->client->children($id) as $item) {
            $itemPath = trim($path.'/'.$item['name'], '/');

            if ($item['mimeType'] === GoogleDriveClient::FOLDER) {
                $this->folders[$itemPath] = $item['id'];
                yield new DirectoryAttributes($itemPath);

                if ($deep) {
                    yield from $this->listContents($itemPath, true);
                }
            } else {
                yield $this->toAttributes($itemPath, $item);
            }
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw UnableToMoveFile::fromLocationTo($source, $destination);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw UnableToCopyFile::fromLocationTo($source, $destination);
    }

    /** @return array<string, mixed>|null */
    protected function file(string $path): ?array
    {
        [$dir, $name] = $this->split($path);
        $parent = $this->folderId($dir, create: false);

        return $parent ? $this->client->find($name, $parent, folder: false) : null;
    }

    protected function attributes(string $path): FileAttributes
    {
        $file = $this->file($path) ?? throw UnableToRetrieveMetadata::create($path, 'metadata', 'Not found on Google Drive.');

        return $this->toAttributes($path, $file);
    }

    /** @param array<string, mixed> $file */
    protected function toAttributes(string $path, array $file): FileAttributes
    {
        return new FileAttributes(
            $path,
            isset($file['size']) ? (int) $file['size'] : null,
            Visibility::PRIVATE,
            isset($file['modifiedTime']) ? strtotime($file['modifiedTime']) : null,
            $file['mimeType'] ?? null,
        );
    }

    /** Folder id of a path under the root folder ('' = the root folder itself). */
    protected function folderId(string $path, bool $create): ?string
    {
        $path = trim($path, '/');

        if (isset($this->folders[$path])) {
            return $this->folders[$path];
        }

        if ($path === '') {
            $root = $this->client->find($this->rootFolder, 'root', folder: true)['id'] ?? null;
            $root ??= $create ? $this->client->createFolder($this->rootFolder, 'root') : null;

            return $root ? $this->folders[''] = $root : null;
        }

        [$parentPath, $name] = $this->split($path);
        $parent = $this->folderId($parentPath, $create);

        if ($parent === null) {
            return null;
        }

        $id = $this->client->find($name, $parent, folder: true)['id'] ?? null;
        $id ??= $create ? $this->client->createFolder($name, $parent) : null;

        return $id ? $this->folders[$path] = $id : null;
    }

    /** @return array{0: string, 1: string} folder, name */
    protected function split(string $path): array
    {
        $path = trim($path, '/');
        $slash = strrpos($path, '/');

        return $slash === false ? ['', $path] : [substr($path, 0, $slash), substr($path, $slash + 1)];
    }
}
