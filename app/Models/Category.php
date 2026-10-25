<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active', 'show_in_nav'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'show_in_nav' => 'boolean'];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function heads(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('can_review')->withTimestamps();
    }
}
