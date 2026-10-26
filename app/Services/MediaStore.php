<?php

namespace App\Services;

use App\Exceptions\InvalidImage;
use App\Models\Media;
use App\Models\User;
use App\Support\ImageVariants;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** The only place media files are written or removed. Files live on the private "local" disk. */
class MediaStore
{
    public const DISK = 'local';

    /** @throws InvalidImage */
    public function store(UploadedFile $file, User $uploader, string $alt, ?string $caption = null, ?string $credit = null, bool $public = false): Media
    {
        $result = ImageVariants::make($file->getRealPath()); // validates by content; the client's file name is never used

        $dir = 'media/'.now()->format('Y/m').'/'.Str::ulid();
        $disk = Storage::disk(self::DISK);

        foreach ($result['variants'] as $name => $variant) {
            $disk->put("{$dir}/{$name}.jpg", $variant['data']);
        }

        return Media::create([
            'uploader_id' => $uploader->id,
            'path' => $dir,
            'width' => $result['variants']['original']['width'],
            'height' => $result['variants']['original']['height'],
            'bytes' => strlen($result['variants']['original']['data']),
            'alt' => $alt,
            'caption' => $caption,
            'credit' => $credit,
            'is_public' => $public,
            'released_at' => $public ? now() : null,
            'released_by' => $public ? $uploader->id : null,
        ]);
    }

    public function delete(Media $media): void
    {
        Storage::disk(self::DISK)->deleteDirectory($media->path);
        $media->delete();
    }
}
