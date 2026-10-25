<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\Article;

class ArticleStats
{
    /** Statuses that are waiting on someone to act. */
    public const PENDING = [
        ArticleStatus::Submitted,
        ArticleStatus::CategoryApproved,
        ArticleStatus::InReview,
        ArticleStatus::Resubmitted,
    ];

    /**
     * Article counts for every status (zero-filled).
     *
     * @param  list<int>|null  $categoryIds  null = all categories
     * @return array<string, int>
     */
    public static function byStatus(?array $categoryIds = null): array
    {
        $counts = Article::query()
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $result = [];
        foreach (ArticleStatus::cases() as $status) {
            $result[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $result;
    }

    public static function pending(array $byStatus): int
    {
        return array_sum(array_map(fn (ArticleStatus $s) => $byStatus[$s->value], self::PENDING));
    }
}
