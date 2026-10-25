<?php

namespace Modules\FacultyMember\Sync;

/**
 * Teachers from a JSON file: {"data": [...]} or a plain list, in this module's record format or in
 * the FMS API's (GET /teachers — read through FmsSource::record()). Until DIU's Faculty Management
 * System has the real teachers this reads the sample shipped with the module (resources/data,
 * dummy teachers in the FMS format).
 */
class JsonFileSource implements FacultyMemberSource
{
    public function __construct(protected string $path) {}

    public static function samplePath(): string
    {
        return module_path('FacultyMember', 'resources/data/faculty-members.json');
    }

    public function records(): array
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new SourceUnavailable(__('facultymember::facultymember.sync.no_file', ['path' => $this->path]));
        }

        return array_map(fn (array $raw): array => FmsSource::looksLikeFms($raw) ? FmsSource::record($raw) : $raw,
            RecordList::from(json_decode((string) file_get_contents($this->path), true)));
    }

    public function describe(): string
    {
        return 'JSON '.basename($this->path);
    }
}
