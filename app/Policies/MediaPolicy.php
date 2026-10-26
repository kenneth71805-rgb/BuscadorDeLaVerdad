<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    public function view(?User $user, Media $media): bool
    {
        if ($media->is_public) {
            return true;
        }

        return $user !== null && ($user->hasRole(Role::Admin, Role::EditorInChief) || $media->uploader_id === $user->id);
    }

    public function update(User $user, Media $media): bool
    {
        return $user->hasRole(Role::Admin, Role::EditorInChief)
            || ($media->uploader_id === $user->id && $user->hasRole(Role::CategoryHead) && ! $media->is_public);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->update($user, $media);
    }

    /** Approving (or withdrawing) a photo for public release is an editorial decision. */
    public function release(User $user, Media $media): bool
    {
        return $user->hasRole(Role::Admin, Role::EditorInChief);
    }
}
