<?php

namespace Platform\Assignments;

use Platform\Contracts\PersonPresenter;

class ManualPersonPresenter implements PersonPresenter
{
    public function __construct(protected ManualPerson $person) {}

    public function name(): string
    {
        return (string) $this->person->name;
    }

    public function designation(): ?string
    {
        return $this->person->designation ?: null;
    }

    public function photoUrl(): ?string
    {
        return null;   // Media usage (P-13) arrives with the Media Library
    }

    public function email(): ?string
    {
        return $this->person->email;
    }

    public function phone(): ?string
    {
        return $this->person->phone;
    }

    public function extension(): ?string
    {
        return null;
    }

    public function profileUrl(): ?string
    {
        return $this->person->profile_url;
    }

    public function isActive(): bool
    {
        return (bool) $this->person->is_active;
    }
}
