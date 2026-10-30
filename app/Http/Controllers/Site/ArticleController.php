<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleView;
use Illuminate\Http\Request;
use App\Support\PublicData;
use App\Support\Seo;
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

        $path = route('site.article', [$article->category->slug, $article->slug], false);
        $image = PublicData::image($article, 'large');
        $meta = Seo::page($article->title, $article->subtitle ?: $article->excerpt(), $path, [
            'type' => 'article',
            'image' => $image ? url($image['url']) : null,
            'published_time' => $article->published_at?->toAtomString(),
            'modified_time' => ($article->corrected_at ?? $article->published_at)?->toAtomString(),
            'section' => $article->category->name,
            'jsonld' => array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'NewsArticle',
                'headline' => \Illuminate\Support\Str::limit($article->title, 110, ''),
                'datePublished' => $article->published_at?->toAtomString(),
                'dateModified' => ($article->corrected_at ?? $article->published_at)?->toAtomString(),
                'author' => [['@type' => 'Person', 'name' => $article->author->name]],
                'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
                'mainEntityOfPage' => url($path),
                'articleSection' => $article->category->name,
                'image' => $image ? [url($image['url'])] : null,
            ]),
        ]);

        return Inertia::render('site/article', [
            'meta' => $meta,
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
