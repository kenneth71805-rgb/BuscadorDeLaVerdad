<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use Database\Factories\ArticleFactory;
use App\Enums\Role;
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

    /** Records a staff member may see: everything for Admin/EIC, own categories for a Head. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole(Role::Admin, Role::EditorInChief)) {
            return $query;
        }

        if ($user->hasRole(Role::CategoryHead)) {
            return $query->whereIn('category_id', $user->categories()->pluck('categories.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(preg_match_all('/\p{L}+/u', $this->body) / 200));
    }

    public function excerpt(int $limit = 180): string
    {
        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', $this->body)), $limit);
    }
}
