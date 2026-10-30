<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Issue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const LIMIT = 5000;

    public function __invoke()
    {
        $xml = Cache::remember('sitemap.xml', 600, fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function build(): string
    {
        $urls = [];
        $add = function (string $path, $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => url($path), 'lastmod' => $lastmod ? Carbon::parse($lastmod)->toAtomString() : null];
        };

        foreach (['/', '/about', '/contact', '/events', '/galleries', '/issues', '/archives'] as $path) {
            $add($path);
        }

        Category::where('is_active', true)->orderBy('sort_order')->get()->each(fn (Category $c) => $add('/'.$c->slug));

        Article::publiclyVisible()->with('category:id,slug')->latest('published_at')->limit(self::LIMIT)->get()
            ->each(fn (Article $a) => $add(route('site.article', [$a->category->slug, $a->slug], false), $a->corrected_at ?? $a->published_at));

        Gallery::onPublicSite()->get()->each(fn (Gallery $g) => $add(route('site.gallery', $g->slug, false), $g->updated_at));
        Issue::publicOnly()->get()->each(fn (Issue $i) => $add(route('site.issue', $i->slug, false), $i->updated_at));

        $first = Article::publiclyVisible()->min('published_at');
        $last = Article::publiclyVisible()->max('published_at');
        if ($first && $last) {
            for ($y = Carbon::parse($first)->year; $y <= Carbon::parse($last)->year; $y++) {
                $add("/archives/{$y}");
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>'
                .($u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '').'</url>'."\n";
        }

        return $xml.'</urlset>'."\n";
    }
}
