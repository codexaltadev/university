<?php

namespace Modules\Administration\Support;

use Modules\Administration\Models\Member;
use Platform\Contracts\PersonPresenter;

/** How a member shows on any place's people list (SAD Part B §4.3); hidden contacts stay hidden there too (FR-ADMN-07). */
class MemberPresenter implements PersonPresenter
{
    public function __construct(protected Member $member) {}

    public function name(): string
    {
        return (string) $this->member->name;
    }

    public function designation(): ?string
    {
        return $this->member->designation ?: null;
    }

    public function photoUrl(): ?string
    {
        return $this->member->firstMedia('photo')?->url('medium');
    }

    public function email(): ?string
    {
        return $this->member->show_email ? $this->member->email : null;
    }

    public function phone(): ?string
    {
        return $this->member->show_phone ? $this->member->phone : null;
    }

    public function extension(): ?string
    {
        return $this->member->show_extension ? $this->member->extension : null;
    }

    /** The profile page, when profiles are on and the member is in the directory. */
    public function profileUrl(): ?string
    {
        return settings('administration.profile_pages', true) && $this->member->isFrontendVisible() ? url($this->member->url()) : null;
    }

    public function isActive(): bool
    {
        return (bool) $this->member->is_active;
    }
}
