<?php

namespace Platform\Contracts;

/**
 * An entity that gets its own dynamic permissions when created (Office, Lab — FR-ROLE-07).
 * Permission key: "{Action}:{Entity}#{id}", e.g. "Manage:Office#12".
 */
interface HasEntityPermissions
{
    /** Short subject name used in the key, e.g. "Office". */
    public static function entityPermissionSubject(): string;

    /** @return list<string> actions, e.g. ["Manage"] */
    public static function entityPermissionActions(): array;

    /** Human label used in the Role UI, e.g. "Registrar Office". */
    public function entityPermissionLabel(): string;
}
