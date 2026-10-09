<?php

namespace Platform\Assignments;

use Platform\Contracts\PersonPresenter;

/**
 * One person as shown on the website: presenter data with the assignment's
 * overrides applied (SAD Part B §4.3) and the role label resolved.
 */
final class PersonView
{
    public function __construct(
        public readonly Assignment $assignment,
        public readonly PersonPresenter $person,
        public readonly string $roleLabel,
    ) {}

    public function name(): string
    {
        return $this->person->name();
    }

    public function designation(): ?string
    {
        return $this->person->designation();
    }

    public function email(): ?string
    {
        return $this->assignment->overrides['email'] ?? $this->person->email();
    }

    public function phone(): ?string
    {
        return $this->assignment->overrides['phone'] ?? $this->person->phone();
    }

    public function extension(): ?string
    {
        return $this->assignment->overrides['extension'] ?? $this->person->extension();
    }

    public function photoUrl(): ?string
    {
        return $this->person->photoUrl();
    }

    public function profileUrl(): ?string
    {
        return $this->person->profileUrl();
    }

    public function roleKey(): string
    {
        return $this->assignment->role_key;
    }
}
