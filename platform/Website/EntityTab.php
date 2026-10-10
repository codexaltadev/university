<?php

namespace Platform\Website;

use Illuminate\Database\Eloquent\Model;

/**
 * A tab / section another module adds to an entity's page (FR-PROG-18, SAD Part A §7):
 * Tuition → Program, Curriculum → Program. The entity module never knows the other one;
 * an inactive module's tabs are simply not registered (AC-PROG-02).
 *
 *     $r->entityTab(Program::class, TuitionTab::class);
 */
abstract class EntityTab
{
    /** Anchor and stable key, e.g. "tuition.fee". */
    abstract public static function key(): string;

    abstract public static function label(): string;

    /** View rendered with $entity and data(). */
    abstract public static function view(): string;

    /** Lower first. */
    public static function sort(): int
    {
        return 50;
    }

    /** False hides the tab for this entity, e.g. no tuition entered yet. */
    public function visible(Model $entity): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function data(Model $entity): array
    {
        return [];
    }
}
