<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\CalendarEvent;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Issue;
use App\Support\PublicData;
use App\Support\Seo;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke()
    {
        $public = fn () => Article::publiclyVisible()->with(['category:id,name,slug', 'author:id,name', 'leadMedia']);

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
            'meta' => Seo::page('Home', null, '/', ['image' => $lead?->leadMedia?->is_public ? url($lead->leadMedia->url('large')) : null]),
            'nav' => PublicData::nav(),
            'lead' => $lead ? PublicData::card($lead) : null,
            'latest' => $latest->map(fn (Article $a) => PublicData::card($a))->all(),
            'sections' => $sections,
            'galleries' => Gallery::onPublicSite()->with(['items' => fn ($q) => $q->where('media.is_public', true)])
                ->latest('published_at')->limit(3)->get()->map(fn (Gallery $g) => [
                    'title' => $g->title, 'url' => route('site.gallery', $g->slug, false),
                    'cover' => ['url' => $g->items->first()->url('medium'), 'alt' => $g->items->first()->alt],
                ]),
            'events' => CalendarEvent::with('category:id,name')->where('is_public', true)->where('starts_at', '>=', now()->startOfDay())
                ->orderBy('starts_at')->limit(3)->get()->map(fn (CalendarEvent $e) => EventController::card($e)),
            'issues' => Issue::publicOnly()->with('cover')->latest('issue_date')->limit(3)->get()
                ->map(fn (Issue $i) => IssueController::card($i)),
        ]);
    }
}
