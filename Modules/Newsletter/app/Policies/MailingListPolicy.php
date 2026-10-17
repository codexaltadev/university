<?php

declare(strict_types=1);

namespace Modules\Newsletter\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Newsletter\Models\MailingList;
use Platform\Policies\ScopedPolicy;

/** MailingList: by permission and scope (Shield, Scope engine). */
class MailingListPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:MailingList');
    }

    public function view(AuthUser $authUser, MailingList $mailingList): bool
    {
        return $this->allows($authUser, 'View:MailingList', $mailingList);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:MailingList');
    }

    public function update(AuthUser $authUser, MailingList $mailingList): bool
    {
        return $this->allows($authUser, 'Update:MailingList', $mailingList);
    }

    public function delete(AuthUser $authUser, MailingList $mailingList): bool
    {
        return $this->allows($authUser, 'Delete:MailingList', $mailingList);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:MailingList');
    }

    public function restore(AuthUser $authUser, MailingList $mailingList): bool
    {
        return $this->allows($authUser, 'Restore:MailingList', $mailingList);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:MailingList');
    }

    public function forceDelete(AuthUser $authUser, MailingList $mailingList): bool
    {
        return $this->allows($authUser, 'ForceDelete:MailingList', $mailingList);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:MailingList');
    }
}
