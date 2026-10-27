<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\User;

class AssignmentPolicy
{
    public function view(User $user, Assignment $a): bool
    {
        return $this->manage($user, $a) || $a->assigned_to === $user->id;
    }

    /** Edit details: Admin, Editor-in-Chief, the Head of that category, or the person who created it. */
    public function update(User $user, Assignment $a): bool
    {
        return $this->manage($user, $a);
    }

    /** Change status: anyone who can manage it, or the person it is assigned to. */
    public function updateStatus(User $user, Assignment $a): bool
    {
        return $this->view($user, $a);
    }

    private function manage(User $user, Assignment $a): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->hasRole(Role::Admin, Role::EditorInChief)) {
            return true;
        }

        return $user->headsCategory($a->category_id) || $a->created_by === $user->id;
    }
}
