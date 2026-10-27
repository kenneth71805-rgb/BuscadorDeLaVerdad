<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\User;

class Deadlines
{
    /** The next open assignments this person is allowed to see, soonest (or most overdue) first. */
    public static function upcoming(User $user, int $limit = 5): array
    {
        return Assignment::query()->visibleTo($user)->whereIn('status', ['open', 'in_progress'])
            ->with(['category:id,name', 'assignee:id,name'])->orderBy('due_at')->limit($limit)->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id, 'title' => $a->title, 'category' => $a->category->name, 'assignee' => $a->assignee->name,
                'due_at' => $a->due_at->toIso8601String(), 'overdue' => $a->isOverdue(), 'url' => route('assignments.edit', $a, false),
            ])->all();
    }
}
