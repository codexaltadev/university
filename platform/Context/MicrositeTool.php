<?php

namespace Platform\Context;

/**
 * A screen that edits something a microsite owns — Menus, Header & Footer, Homepage,
 * Statistics, Pages (UI/UX Part A §4.1) — registered with $r->micrositeTool(Page::class).
 * The entity editors' "Microsite" button and "Working in" open it for one owner.
 * Its label and icon are the page's navigation label and icon.
 */
interface MicrositeTool
{
    /** May the current user use this screen for that owner ("site:0", "program.program:12")? */
    public static function canUseFor(string $ownerKey): bool;

    /** The screen opened on that owner. */
    public static function urlFor(string $ownerKey): string;

    /** Has that owner set this up (its own menu, homepage …)? Shown as ✓ / ! on the "Microsite" button (UI/UX Part A §4.2). */
    public static function isSetUpFor(string $ownerKey): bool;
}
