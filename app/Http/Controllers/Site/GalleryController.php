<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Media;
use App\Support\PublicData;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::onPublicSite()
            ->with(['items' => fn ($q) => $q->where('media.is_public', true)])
            ->latest('published_at')->paginate(12)
            ->through(fn (Gallery $g) => [
                'title' => $g->title, 'description' => $g->description, 'url' => route('site.gallery', $g->slug, false),
                'count' => $g->items->count(),
                'cover' => ['url' => $g->items->first()->url('medium'), 'alt' => $g->items->first()->alt],
                'published_at' => $g->published_at?->toIso8601String(),
            ]);

        return Inertia::render('site/galleries', ['nav' => PublicData::nav(), 'galleries' => $galleries]);
    }

    public function show(string $slug)
    {
        $gallery = Gallery::where('status', 'published')->where('slug', $slug)->firstOrFail();

        // Only photos an editor approved for public release; the rest are invisible here.
        $photos = $gallery->items()->where('media.is_public', true)->get()
            ->map(fn (Media $m) => ['id' => $m->id, 'url' => $m->url('large'), 'thumb' => $m->url('medium'), 'alt' => $m->alt,
                'caption' => $m->caption, 'credit' => $m->credit, 'width' => $m->width, 'height' => $m->height]);

        abort_if($photos->isEmpty(), 404);

        return Inertia::render('site/gallery', [
            'nav' => PublicData::nav(),
            'gallery' => ['title' => $gallery->title, 'description' => $gallery->description, 'published_at' => $gallery->published_at?->toIso8601String()],
            'photos' => $photos,
        ]);
    }
}
