<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ArticleRevision;
use App\Models\User;

class ArticleRevisionPolicy
{
    public function view(User $user, ArticleRevision $revision): bool
    {
        return $user->hasRole(Role::Admin, Role::EditorInChief) || $user->headsCategory($revision->article->category_id);
    }

    /** Approving or declining a correction is the Editor-in-Chief's decision alone. */
    public function decide(User $user, ArticleRevision $revision): bool
    {
        return $user->hasRole(Role::EditorInChief) && $revision->status === 'pending';
    }
}
