<?php

namespace App\Http\Controllers\Category;

use App\Enums\ArticleStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleStats;
use App\Services\Deadlines;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = $request->user()->categories()->orderBy('sort_order')->get(['categories.id', 'name']);
        $ids = $categories->pluck('id')->all();

        return Inertia::render('category/dashboard', [
            'deadlines' => Deadlines::upcoming($request->user()),
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
            'byStatus' => ArticleStats::byStatus($ids),
            'needsAttention' => Article::with('category:id,name')
                ->whereIn('category_id', $ids)
                ->whereIn('status', [ArticleStatus::Draft->value, ArticleStatus::RevisionRequested->value])
                ->latest('updated_at')->limit(10)->get()
                ->map(fn (Article $a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'category' => $a->category->name,
                    'status' => $a->status->value,
                    'updated_at' => $a->updated_at?->toIso8601String(),
                ]),
        ]);
    }
}
