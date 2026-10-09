<?php

namespace Platform\Contracts;

/**
 * How the website shows a person from any source (SAD Part B §4.3).
 * The frontend always reads through this, then applies the assignment's overrides.
 */
interface PersonPresenter
{
    public function name(): string;

    public function designation(): ?string;

    public function photoUrl(): ?string;

    public function email(): ?string;

    public function phone(): ?string;

    public function extension(): ?string;

    public function profileUrl(): ?string;

    public function isActive(): bool;
}
