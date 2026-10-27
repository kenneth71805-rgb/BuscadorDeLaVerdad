<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\ArticleView;
use App\Models\Category;
use Illuminate\Support\Carbon;

/** Real numbers only. Anything we do not measure is reported as "not recorded", never estimated. */
class ReportService
{
    /** Submissions waiting longer than this many days are flagged as overdue. */
    public const OVERDUE_DAYS = 3;

    /**
     * @param  list<int>|null  $categoryIds  null means every category
     */
    public function build(Carbon $from, Carbon $to, ?array $categoryIds): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $scope = fn ($q) => $categoryIds === null ? $q : $q->whereIn('category_id', $categoryIds);

        // Published per day
        $published = $scope(Article::query())->where('status', 'published')->whereBetween('published_at', $range)->get(['id', 'published_at']);
        $byDay = $published->groupBy(fn ($a) => $a->published_at->format('Y-m-d'))->map->count()->sortKeys();

        // Submissions by category (articles created in the period, with where they stand now)
        $rows = $scope(Article::query())->whereBetween('created_at', $range)->selectRaw('category_id, status, count(*) as total')->groupBy('category_id', 'status')->get();
        $pendingStatuses = array_map(fn ($s) => $s->value, ArticleStats::PENDING);
        $byCategory = Category::query()->when($categoryIds !== null, fn ($q) => $q->whereIn('id', $categoryIds))->orderBy('sort_order')->get()
            ->map(function (Category $c) use ($rows, $pendingStatuses) {
                $mine = $rows->where('category_id', $c->id);

                return [
                    'category' => $c->name,
                    'total' => (int) $mine->sum('total'),
                    'published' => (int) $mine->where('status', 'published')->sum('total'),
                    'pending' => (int) $mine->whereIn('status', $pendingStatuses)->sum('total'),
                    'rejected' => (int) $mine->where('status', 'rejected')->sum('total'),
                ];
            })->values();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'published_total' => $published->count(),
            'published_by_day' => $byDay->map(fn ($count, $date) => ['date' => $date, 'count' => $count])->values(),
            'by_category' => $byCategory,
            'turnaround' => $this->turnaround($published->pluck('id')->all()),
            'stuck' => $this->stuck($categoryIds),
            'readership' => $this->readership($range, $categoryIds),
        ];
    }

    /** Average hours from first submission to publication, for stories published in the period. */
    private function turnaround(array $articleIds): array
    {
        if ($articleIds === []) {
            return ['hours' => null, 'sample' => 0];
        }

        $hours = ArticleStatusHistory::whereIn('article_id', $articleIds)->whereIn('to_status', ['submitted', 'published'])
            ->orderBy('id')->get(['article_id', 'to_status', 'created_at'])->groupBy('article_id')
            ->map(function ($events) {
                $submitted = $events->firstWhere('to_status.value', 'submitted');
                $published = $events->firstWhere('to_status.value', 'published');

                return $submitted && $published ? $submitted->created_at->diffInMinutes($published->created_at) / 60 : null;
            })->filter(fn ($h) => $h !== null);

        return ['hours' => $hours->isEmpty() ? null : round($hours->avg(), 1), 'sample' => $hours->count()];
    }

    /** Pending submissions, oldest first, flagged when they have waited too long. */
    private function stuck(?array $categoryIds): array
    {
        $pending = array_map(fn ($s) => $s->value, ArticleStats::PENDING);

        return Article::query()->with(['category:id,name', 'author:id,name'])->whereIn('status', $pending)
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->orderBy('updated_at')->limit(10)->get()
            ->map(fn (Article $a) => [
                'id' => $a->id, 'title' => $a->title, 'category' => $a->category->name, 'author' => $a->author->name,
                'status' => $a->status->value, 'days' => (int) $a->updated_at->diffInDays(now()),
                'overdue' => $a->updated_at->lte(now()->subDays(self::OVERDUE_DAYS)),
            ])->all();
    }

    private function readership(array $range, ?array $categoryIds): array
    {
        if (! ArticleView::query()->exists()) {
            return ['recorded' => false, 'total' => 0, 'popular' => []];
        }

        $base = ArticleView::query()->whereBetween('viewed_on', [$range[0]->toDateString(), $range[1]->toDateString()])
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('article_id', Article::whereIn('category_id', $categoryIds)->select('id')));

        $top = (clone $base)->selectRaw('article_id, sum(views) as total')->groupBy('article_id')->orderByDesc('total')->limit(10)->get();
        $titles = Article::with('category:id,name')->whereIn('id', $top->pluck('article_id'))->get()->keyBy('id');

        return [
            'recorded' => true,
            'total' => (int) (clone $base)->sum('views'),
            'popular' => $top->map(fn ($r) => ['title' => $titles[$r->article_id]->title ?? '(removed)', 'category' => $titles[$r->article_id]->category->name ?? '', 'views' => (int) $r->total])->all(),
        ];
    }
}
