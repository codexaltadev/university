<?php

namespace Modules\FacultyMember\Support;

use Illuminate\Support\HtmlString;
use Modules\FacultyMember\Models\FacultyMember;
use Platform\Contracts\PersonPresenter;

/** How a teacher shows on any place's people list (SAD Part B §4.3); hidden contacts stay hidden there too (FR-FM-09). */
class FacultyMemberPresenter implements PersonPresenter
{
    public function __construct(protected FacultyMember $member) {}

    public function name(): string
    {
        return (string) $this->member->name;
    }

    public function designation(): ?string
    {
        return $this->member->designationLabel();
    }

    /** The faculty site's picture (not copied, DB-03 §8 v1.7). */
    public function photoUrl(): ?string
    {
        return filled($this->member->photo_url) ? (string) $this->member->photo_url : null;
    }

    public function email(): ?string
    {
        return $this->member->show_email ? $this->member->email : null;
    }

    /**
     * The public email as a mailto link written in character references, which simple
     * address harvesters do not decode (FR-FM-09). Null when hidden.
     */
    public function protectedEmailLink(): ?HtmlString
    {
        if (($email = $this->email()) === null) {
            return null;
        }
        $encoded = implode('', array_map(fn (string $c): string => '&#'.mb_ord($c).';', mb_str_split($email)));

        return new HtmlString('<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;'.$encoded.'">'.$encoded.'</a>');
    }

    public function phone(): ?string
    {
        return $this->member->show_phone ? $this->member->phone : null;
    }

    public function extension(): ?string
    {
        return $this->member->show_extension ? $this->member->extension : null;
    }

    public function profileUrl(): ?string
    {
        return settings('faculty-member.profile_pages', true) && $this->member->isFrontendVisible() ? url($this->member->url()) : null;
    }

    public function isActive(): bool
    {
        return (bool) $this->member->is_active;
    }
}
