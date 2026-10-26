<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffProfile extends Model
{
    protected $fillable = ['name', 'position', 'category_id', 'bio', 'link_url', 'sort_order'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'archived_at' => 'datetime'];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'photo_media_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Profiles an Admin has approved for the public site. Never includes private ones. */
    public function scopePublicOnly(Builder $q): Builder
    {
        return $q->where('is_public', true);
    }
}
