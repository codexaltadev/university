<?php

namespace Platform\Context;

/**
 * For a microsite screen with a public ?string $owner (Menus, Header & Footer, Homepage,
 * Statistics): it opens on the "Working in" owner unless one is asked for, and the owner
 * it shows becomes the current one (UI/UX Part A §4.1).
 */
trait FollowsWorkingIn
{
    /** @param list<string> $allowed */
    protected function ownerOnOpen(array $allowed): ?string
    {
        return in_array($this->owner, $allowed, true) ? $this->owner : WorkingIn::pick($allowed);
    }

    public function renderingFollowsWorkingIn(): void
    {
        if (filled($this->owner) && WorkingIn::current() !== $this->owner) {
            WorkingIn::set($this->owner);
        }
    }
}
