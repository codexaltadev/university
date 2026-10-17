<?php

namespace Modules\FacultyMember\Sync;

/** The record list out of a decoded body: {"data": [...]} or a plain list. */
final class RecordList
{
    /** @return list<array<string, mixed>> */
    public static function from(mixed $body, bool $allowEmpty = false): array
    {
        $list = is_array($body) && array_key_exists('data', $body) ? $body['data'] : $body;

        if (! is_array($list) || ! array_is_list($list)) {
            throw new SourceUnavailable(__('facultymember::facultymember.sync.bad_format'));
        }
        if ($list === [] && ! $allowEmpty) {
            throw new SourceUnavailable(__('facultymember::facultymember.sync.empty'));   // BRL-FM-05
        }

        return array_values(array_filter($list, 'is_array'));
    }
}
