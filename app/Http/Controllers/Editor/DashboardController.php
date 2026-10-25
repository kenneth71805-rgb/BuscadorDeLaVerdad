<?php

namespace App\Http\Controllers\Editor;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Services\ArticleStats;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $pending = array_map(fn (ArticleStatus $s) => $s->value, ArticleStats::PENDING);

        return Inertia::render('editor/dashboard', [
            'byStatus' => ArticleStats::byStatus(),
            'queue' => Article::with(['category:id,name', 'author:id,name'])
                ->whereIn('status', [ArticleStatus::CategoryApproved->value, ArticleStatus::InReview->value])
                ->oldest('updated_at')->limit(10)->get()
                ->map(fn (Article $a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'category' => $a->category->name,
                    'author' => $a->author->name,
                    'status' => $a->status->value,
                    'updated_at' => $a->updated_at?->toIso8601String(),
                ]),
            'byCategory' => Category::query()->orderBy('sort_order')
                ->withCount(['articles as pending_count' => fn ($q) => $q->whereIn('status', $pending)])
                ->get(['id', 'name'])
                ->map(fn (Category $c) => ['id' => $c->id, 'name' => $c->name, 'pending' => $c->pending_count]),
        ]);
    }
}
