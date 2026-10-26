<?php

namespace App\Http\Controllers\Newsroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryRequest;
use App\Models\AuditLog;
use App\Models\Gallery;
use App\Models\Media;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        return Inertia::render('newsroom/galleries/index', [
            'galleries' => Gallery::withCount(['items', 'items as public_items_count' => fn ($q) => $q->where('media.is_public', true)])
                ->latest('id')->get()->map(fn (Gallery $g) => [
                    'id' => $g->id, 'title' => $g->title, 'slug' => $g->slug, 'status' => $g->status,
                    'items' => $g->items_count, 'public_items' => $g->public_items_count,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('newsroom/galleries/create', ['media' => $this->library()]);
    }

    public function store(GalleryRequest $request)
    {
        $data = $request->validated();
        $gallery = new Gallery(['title' => $data['title'], 'description' => $data['description'] ?? null]);
        $gallery->slug = $this->uniqueSlug($data['title']);
        $gallery->created_by = $request->user()->id;
        $this->applyStatus($gallery, $data['status']);
        $gallery->save();
        $this->syncItems($gallery, $data['items'] ?? []);
        AuditLog::record('gallery.created', $gallery, ['status' => $gallery->status], $request->user());

        return redirect()->route('newsroom.galleries.index')->with('success', 'Gallery saved.');
    }

    public function edit(Gallery $gallery)
    {
        return Inertia::render('newsroom/galleries/edit', [
            'gallery' => $gallery->only(['id', 'title', 'slug', 'description', 'status']) + ['items' => $gallery->items()->pluck('media.id')->all()],
            'media' => $this->library(),
        ]);
    }

    public function update(GalleryRequest $request, Gallery $gallery)
    {
        $data = $request->validated();
        $gallery->fill(['title' => $data['title'], 'description' => $data['description'] ?? null]); // slug stays fixed
        $this->applyStatus($gallery, $data['status']);
        $gallery->save();
        $this->syncItems($gallery, $data['items'] ?? []);
        AuditLog::record('gallery.updated', $gallery, ['status' => $gallery->status], $request->user());

        return redirect()->route('newsroom.galleries.index')->with('success', 'Gallery saved.');
    }

    private function applyStatus(Gallery $gallery, string $status): void
    {
        $gallery->status = $status;
        if ($status === 'published' && ! $gallery->published_at) {
            $gallery->published_at = now();
        }
    }

    /** Keeps the order the editor chose. */
    private function syncItems(Gallery $gallery, array $ids): void
    {
        $gallery->items()->sync(collect($ids)->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());
    }

    private function library(): array
    {
        return Media::latest('id')->limit(120)->get()
            ->map(fn (Media $m) => ['id' => $m->id, 'url' => $m->url('thumb'), 'alt' => $m->alt, 'is_public' => $m->is_public])->all();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'gallery', 80, '');
        $slug = $base;
        for ($i = 2; Gallery::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
