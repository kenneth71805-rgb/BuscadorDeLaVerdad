<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, SoftDeletes;

    // status, published_at and scheduled_for are NOT mass-assignable:
    // they change only through App\Services\ArticleWorkflow.
    protected $fillable = ['category_id', 'author_id', 'slug', 'title', 'subtitle', 'body'];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
            'scheduled_for' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ArticleStatusHistory::class)->orderBy('id');
    }

    /**
     * The ONLY query scope public pages, search, feeds and related-story
     * queries may use. Guests must never see anything outside it.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ArticleStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('category', fn (Builder $q) => $q->where('is_active', true));
    }
}
