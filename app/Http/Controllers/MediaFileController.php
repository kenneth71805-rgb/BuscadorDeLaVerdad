<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Serves image files from private storage, only to people allowed to see them. */
class MediaFileController extends Controller
{
    public function __invoke(Request $request, Media $media, string $variant)
    {
        abort_unless(in_array($variant, Media::VARIANTS, true), 404);

        if (! $media->is_public) {
            $user = $request->user();
            // 404, not 403: do not reveal that an unapproved photo exists.
            abort_unless($user && Gate::forUser($user)->allows('view', $media), 404);
        }

        $disk = Storage::disk('local');
        abort_unless($disk->exists($media->filePath($variant)), 404);

        return response()->file($disk->path($media->filePath($variant)), [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $media->is_public ? 'public, max-age=86400' : 'private, no-store',
        ]);
    }
}
