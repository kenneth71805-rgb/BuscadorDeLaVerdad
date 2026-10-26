<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Support\PublicData;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke()
    {
        $public = fn () => Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name']);

        $lead = $public()->latest('published_at')->first();
        $latest = $public()->when($lead, fn ($q) => $q->whereKeyNot($lead->id))
            ->latest('published_at')->limit(8)->get();

        $sections = Category::query()->where('is_active', true)->where('show_in_nav', true)
            ->orderBy('sort_order')->get()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'stories' => $public()->where('category_id', $c->id)->latest('published_at')->limit(3)->get()
                    ->map(fn (Article $a) => PublicData::card($a))->all(),
            ])
            ->filter(fn (array $s) => count($s['stories']) > 0)->take(4)->values();

        return Inertia::render('site/home', [
            'nav' => PublicData::nav(),
            'lead' => $lead ? PublicData::card($lead) : null,
            'latest' => $latest->map(fn (Article $a) => PublicData::card($a))->all(),
            'sections' => $sections,
        ]);
    }
}
