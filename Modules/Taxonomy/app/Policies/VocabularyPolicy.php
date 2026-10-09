<?php

declare(strict_types=1);

namespace Modules\Taxonomy\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Platform\Policies\ScopedPolicy;
use Platform\Taxonomy\Vocabulary;

/** Vocabularies come from modules: they can be renamed, never created or deleted here. */
class VocabularyPolicy extends ScopedPolicy
{
    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function view(AuthUser $authUser, Vocabulary $vocabulary): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function update(AuthUser $authUser, Vocabulary $vocabulary): bool
    {
        return $this->allows($authUser, 'Manage:Taxonomy');
    }

    public function create(AuthUser $authUser): bool
    {
        return false;
    }

    public function delete(AuthUser $authUser, Vocabulary $vocabulary): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }
}
