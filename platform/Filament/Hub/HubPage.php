<?php

namespace Platform\Filament\Hub;

/** A part of an Entity Hub, registered with $r->entityPage() (FR-GEN-86). */
interface HubPage
{
    /** The URL segment and page key, e.g. "departments" → /admin/faculties/{record}/departments. */
    public static function hubKey(): string;
}
