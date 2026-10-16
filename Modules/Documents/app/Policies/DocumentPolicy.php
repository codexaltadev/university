<?php

declare(strict_types=1);

namespace Modules\Documents\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Modules\Documents\Models\Document;
use Platform\Policies\ScopedPolicy;

/** Documents: by permission and scope — an entity user manages the documents shown in their entities. */
class DocumentPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Document');
    }

    public function view(AuthUser $authUser, Document $document): bool
    {
        return $this->allows($authUser, 'View:Document', $document);
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Create:Document');
    }

    public function update(AuthUser $authUser, Document $document): bool
    {
        return $this->allows($authUser, 'Update:Document', $document);
    }

    public function delete(AuthUser $authUser, Document $document): bool
    {
        return $this->allows($authUser, 'Delete:Document', $document);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Document');
    }

    public function restore(AuthUser $authUser, Document $document): bool
    {
        return $this->allows($authUser, 'Restore:Document', $document);
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Document');
    }

    public function forceDelete(AuthUser $authUser, Document $document): bool
    {
        return $this->allows($authUser, 'ForceDelete:Document', $document);
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Document');
    }
}
