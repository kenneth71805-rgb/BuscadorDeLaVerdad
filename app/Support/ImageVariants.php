<?php

namespace App\Support;

use App\Exceptions\InvalidImage;

/**
 * Turns an uploaded file into clean JPEG variants using GD.
 *
 * Safety properties:
 *  - the file is judged by its actual contents (getimagesize + a real decode), never by name or client MIME type
 *  - only JPEG, PNG and WebP are accepted (no SVG, GIF, or anything with embedded scripts)
 *  - every output is a freshly encoded JPEG, so EXIF/GPS metadata and any appended data are discarded
 *  - size and pixel limits stop oversized files and decompression bombs
 */
class ImageVariants
{
    public const MAX_BYTES = 8 * 1024 * 1024;

    public const MAX_PIXELS = 24_000_000;

    /** Target widths. Images are never enlarged. */
    public const SIZES = ['thumb' => 400, 'medium' => 900, 'large' => 1600, 'original' => 2400];

    /** @return array{width:int,height:int,variants:array<string,array{data:string,width:int,height:int}>} */
    public static function make(string $path): array
    {
        if (! extension_loaded('gd')) {
            throw new InvalidImage('Image processing is not available on this server (the PHP GD extension is missing).');
        }

        $bytes = @filesize($path);
        if (! $bytes || $bytes > self::MAX_BYTES) {
            throw new InvalidImage('The image must be larger than 0 bytes and no bigger than 8 MB.');
        }

        $info = @getimagesize($path);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidImage('Only JPEG, PNG and WebP images are accepted.');
        }

        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            throw new InvalidImage('The image dimensions are too large (maximum about 24 megapixels).');
        }

        $source = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            default => @imagecreatefromwebp($path),
        };
        if (! $source) {
            throw new InvalidImage('The file could not be read as an image. It may be corrupted.');
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $source = self::applyOrientation($source, $path);
        } else {
            $source = self::flattenOnWhite($source); // PNG/WebP transparency becomes white
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $variants = [];

        foreach (self::SIZES as $name => $target) {
            $w = min($width, $target);
            $h = (int) max(1, round($height * ($w / $width)));

            if ($w === $width) {
                $copy = $source;
            } else {
                $copy = imagecreatetruecolor($w, $h);
                imagecopyresampled($copy, $source, 0, 0, 0, 0, $w, $h, $width, $height);
            }

            imageinterlace($copy, true);
            ob_start();
            imagejpeg($copy, null, 82);
            $data = (string) ob_get_clean();

            if ($copy !== $source) {
                imagedestroy($copy);
            }
            $variants[$name] = ['data' => $data, 'width' => $w, 'height' => $h];
        }

        imagedestroy($source);

        return ['width' => $width, 'height' => $height, 'variants' => $variants];
    }

    private static function applyOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated ?: $image;
    }

    private static function flattenOnWhite(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $flat = imagecreatetruecolor($w, $h);
        imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
        imagealphablending($flat, true);
        imagecopy($flat, $image, 0, 0, 0, 0, $w, $h);
        imagedestroy($image);

        return $flat;
    }
}
