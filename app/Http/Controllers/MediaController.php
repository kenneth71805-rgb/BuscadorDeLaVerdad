<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exceptions\InvalidImage;
use App\Models\AuditLog;
use App\Models\Media;
use App\Services\MediaStore;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class MediaController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();

        $items = Media::query()->with('uploader:id,name')
            ->when(! $user->hasRole(Role::Admin, Role::EditorInChief), fn ($q) => $q->where('uploader_id', $user->id))
            ->latest('id')->paginate(24)->withQueryString()
            ->through(fn (Media $m) => [
                'id' => $m->id, 'url' => $m->url('thumb'), 'alt' => $m->alt, 'caption' => $m->caption, 'credit' => $m->credit,
                'is_public' => $m->is_public, 'uploader' => $m->uploader->name, 'width' => $m->width, 'height' => $m->height,
                'can_edit' => $user->can('update', $m), 'can_delete' => $user->can('delete', $m), 'can_release' => $user->can('release', $m),
            ]);

        return Inertia::render('media/index', ['items' => $items]);
    }

    public function store(Request $request, MediaStore $store)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:8192'], // content is verified for real inside ImageVariants
            'alt' => ['required', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $media = $store->store($request->file('file'), $request->user(), $data['alt'], $data['caption'] ?? null, $data['credit'] ?? null);
        } catch (InvalidImage $e) {
            throw ValidationException::withMessages(['file' => $e->getMessage()]);
        }

        AuditLog::record('media.uploaded', $media, [], $request->user());

        return back()->with('success', 'Photo uploaded. An editor must approve it before it can appear on the public site.');
    }

    public function update(Request $request, Media $media)
    {
        $this->authorize('update', $media);

        $media->update($request->validate([
            'alt' => ['required', 'string', 'max:300'],
            'caption' => ['nullable', 'string', 'max:500'],
            'credit' => ['nullable', 'string', 'max:150'],
        ]));
        AuditLog::record('media.updated', $media, [], $request->user());

        return back()->with('success', 'Details saved.');
    }

    public function release(Request $request, Media $media)
    {
        $this->authorize('release', $media);
        $public = $request->boolean('public');

        $media->forceFill([
            'is_public' => $public,
            'released_at' => $public ? now() : null,
            'released_by' => $public ? $request->user()->id : null,
        ])->save();
        AuditLog::record($public ? 'media.released' : 'media.withdrawn', $media, [], $request->user());

        return back()->with('success', $public ? 'Approved for public release.' : 'Withdrawn from public view.');
    }

    public function destroy(Request $request, Media $media, MediaStore $store)
    {
        $this->authorize('delete', $media);

        if ($media->inUse()) {
            throw ValidationException::withMessages(['delete' => 'This photo is attached to an article or profile. Remove it there first.']);
        }

        AuditLog::record('media.deleted', $media, [], $request->user());
        $store->delete($media);

        return back()->with('success', 'Photo deleted.');
    }
}
