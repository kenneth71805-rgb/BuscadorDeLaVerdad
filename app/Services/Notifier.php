<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\StaffNotification;
use Illuminate\Support\Collection;

class Notifier
{
    /**
     * @param  iterable<User>  $users
     * @param  User|null  $except  never notify the person who caused the event
     */
    public static function send(iterable $users, string $message, string $url, string $kind, ?User $except = null): int
    {
        $sent = 0;
        foreach (Collection::make($users)->unique('id') as $user) {
            if (! $user->is_active || $user->role === null || ($except && $user->is($except))) {
                continue;
            }
            $user->notify(new StaffNotification($message, $url, $kind));
            $sent++;
        }

        return $sent;
    }

    /** Heads who may review this category (their own submissions excluded by the caller). */
    public static function reviewers(int $categoryId): Collection
    {
        return User::query()->where('role', Role::CategoryHead->value)->where('is_active', true)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId)->where('category_user.can_review', true))->get();
    }

    public static function editors(): Collection
    {
        return User::query()->where('role', Role::EditorInChief->value)->where('is_active', true)->get();
    }
}
