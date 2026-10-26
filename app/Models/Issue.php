<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $fillable = ['title', 'description', 'issue_date', 'cover_media_id'];

    protected function casts(): array
    {
        return ['issue_date' => 'date', 'is_public' => 'boolean'];
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function scopePublicOnly(Builder $q): Builder
    {
        return $q->where('is_public', true)->whereNotNull('pdf_path');
    }
}
