<?php

namespace App\Support;

use Illuminate\Support\Str;

/** Builds the search-engine and social-sharing details that are written into the page on the server. */
class Seo
{
    public const DEFAULT_DESCRIPTION = 'Los Sueños – Buscador De La Verdad, the official student publication of Kolehiyo ng Lungsod ng Dasmariñas.';

    public static function page(string $title, ?string $description, string $path, array $extra = []): array
    {
        return array_merge([
            'title' => $title,
            'description' => self::clean($description) ?: self::DEFAULT_DESCRIPTION,
            'canonical' => url($path),
            'type' => 'website',
            'image' => null,
            'robots' => null,
            'jsonld' => null,
        ], $extra);
    }

    public static function clean(?string $text, int $limit = 200): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $text))), $limit, '…');
    }

    /** Canonical path for a paginated listing: page 1 has no query string. */
    public static function paged(string $path): string
    {
        $page = (int) request()->query('page', 1);

        return $page > 1 ? "{$path}?page={$page}" : $path;
    }
}
