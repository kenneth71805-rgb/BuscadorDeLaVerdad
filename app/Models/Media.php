<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $guarded = []; // rows are only created by App\Services\MediaStore

    public const VARIANTS = ['thumb', 'medium', 'large', 'original'];

    protected function casts(): array
    {
        return ['is_public' => 'boolean', 'released_at' => 'datetime'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /** Address of a variant. Files are private; this route decides who may see them. */
    public function url(string $variant = 'medium'): string
    {
        return route('media.file', [$this->id, $variant], false);
    }

    public function filePath(string $variant): string
    {
        return "{$this->path}/{$variant}.jpg";
    }

    public function inUse(): bool
    {
        return Article::withTrashed()->where('lead_media_id', $this->id)->exists()
            || StaffProfile::where('photo_media_id', $this->id)->exists();
    }
}
