<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    public const STATUSES = ['open' => 'Open', 'in_progress' => 'In progress', 'done' => 'Done', 'cancelled' => 'Cancelled'];

    protected $fillable = ['category_id', 'assigned_to', 'title', 'description', 'due_at'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'reminded_at' => 'datetime', 'overdue_notified_at' => 'datetime'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['open', 'in_progress'], true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at->isPast();
    }

    /** Admin/EIC see all; a Head sees their categories' assignments and anything assigned to or created by them. */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        if ($user->hasRole(Role::Admin, Role::EditorInChief)) {
            return $q;
        }

        return $q->where(fn ($w) => $w
            ->whereIn('category_id', $user->categories()->pluck('categories.id'))
            ->orWhere('assigned_to', $user->id)->orWhere('created_by', $user->id));
    }
}
