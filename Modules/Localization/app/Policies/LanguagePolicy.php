<?php

declare(strict_types=1);

namespace Modules\Localization\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Localization\Language;
use Platform\Policies\ScopedPolicy;

/** System table: no trash, restore or replicate. The default language is never deleted (BRL-LANG-01). */
class LanguagePolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Language');
    }

    public function view(AuthUser $authUser, Language $language): bool
    {
        return $this->allows($authUser, 'View:Language');
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Language');
    }

    public function update(AuthUser $authUser, Language $language): bool
    {
        return $this->allows($authUser, 'Update:Language');
    }

    public function delete(AuthUser $authUser, Language $language): bool
    {
        return ! $language->is_default && $this->allows($authUser, 'Delete:Language');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Language');
    }
}
