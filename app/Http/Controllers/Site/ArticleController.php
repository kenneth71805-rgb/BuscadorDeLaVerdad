<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use App\Support\PublicData;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function __invoke(Request $request, string $categorySlug, string $articleSlug)
    {
        // Never use implicit model binding here: it would find drafts.
        $article = Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name', 'leadMedia'])
            ->where('slug', $articleSlug)
            ->whereHas('category', fn ($q) => $q->where('slug', $categorySlug))
            ->firstOrFail();

        ArticleView::record($article, $request);

        $related = Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name', 'leadMedia'])
            ->where('category_id', $article->category_id)->whereKeyNot($article->id)
            ->latest('published_at')->limit(3)->get();

        return Inertia::render('site/article', [
            'nav' => PublicData::nav(),
            'article' => PublicData::card($article) + [
                'lead' => PublicData::image($article, 'large'),
                'corrected_at' => $article->corrected_at?->toIso8601String(),
                // Only notices an editor chose to make public; internal reasons and pending text never leave the newsroom.
                'corrections' => $article->revisions()->where('status', 'applied')->whereNotNull('public_note')->get()
                    ->map(fn ($r) => ['note' => $r->public_note, 'at' => $r->decided_at?->toIso8601String()])->values(),
                'paragraphs' => array_values(array_filter(preg_split('/\R{2,}/', trim($article->body)))),
            ],
            'related' => $related->map(fn (Article $a) => PublicData::card($a))->all(),
        ]);
    }
}
