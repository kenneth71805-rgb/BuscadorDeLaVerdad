<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    // role, is_active, can_override and can_publish are deliberately NOT
    // fillable: users must never be able to grant themselves privileges.
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Match the database defaults so brand-new instances never hold null flags.
    protected $attributes = [
        'is_active' => true,
        'can_override' => false,
        'can_publish' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'can_override' => 'boolean',
            'can_publish' => 'boolean',
        ];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('can_review')->withTimestamps();
    }

    public function hasRole(Role ...$roles): bool
    {
        return (bool) $this->is_active && $this->role !== null && in_array($this->role, $roles, true);
    }

    public function headsCategory(int $categoryId): bool
    {
        return $this->hasRole(Role::CategoryHead)
            && $this->categories()->whereKey($categoryId)->exists();
    }

    public function canReviewCategory(int $categoryId): bool
    {
        return $this->hasRole(Role::CategoryHead)
            && $this->categories()->whereKey($categoryId)->wherePivot('can_review', true)->exists();
    }
}
