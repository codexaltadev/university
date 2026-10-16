<?php

namespace Modules\Alumni\Support;

use Modules\Alumni\Models\Alumnus;
use Platform\Contracts\PersonPresenter;

/** How a graduate shows on any place's people list; hidden contacts stay hidden there too (FR-ALUM-06). */
class AlumnusPresenter implements PersonPresenter
{
    public function __construct(protected Alumnus $alumnus) {}

    public function name(): string
    {
        return (string) $this->alumnus->name;
    }

    public function designation(): ?string
    {
        return $this->alumnus->headline();
    }

    public function photoUrl(): ?string
    {
        return $this->alumnus->firstMedia('photo')?->url('medium');
    }

    public function email(): ?string
    {
        return $this->alumnus->show_email ? $this->alumnus->email : null;
    }

    public function phone(): ?string
    {
        return $this->alumnus->show_phone ? $this->alumnus->phone : null;
    }

    public function extension(): ?string
    {
        return null;
    }

    public function profileUrl(): ?string
    {
        return $this->alumnus->isFrontendVisible() ? url($this->alumnus->url()) : null;
    }

    public function isActive(): bool
    {
        return (bool) $this->alumnus->is_active;
    }
}
