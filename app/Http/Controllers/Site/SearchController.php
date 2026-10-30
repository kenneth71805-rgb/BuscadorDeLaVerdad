<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\PublicData;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $results = null;

        if (mb_strlen($q) >= 2) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $results = Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name', 'leadMedia'])
                ->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)->orWhere('body', 'like', $like))
                ->latest('published_at')->paginate(10)->withQueryString()
                ->through(fn (Article $a) => PublicData::card($a));
        }

        return Inertia::render('site/search', [
            'meta' => Seo::page('Search', null, '/search', ['robots' => 'noindex,follow']),'nav' => PublicData::nav(), 'q' => $q, 'results' => $results]);
    }
}
