<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gallery extends Model
{
    protected $fillable = ['title', 'description'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'gallery_items')->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** Published galleries that contain at least one photo approved for public release. */
    public function scopeOnPublicSite(Builder $q): Builder
    {
        return $q->where('status', 'published')->whereHas('items', fn ($i) => $i->where('media.is_public', true));
    }
}
