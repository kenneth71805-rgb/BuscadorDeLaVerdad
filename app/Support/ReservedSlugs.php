<?php

namespace App\Support;

/** Section slugs that would collide with real pages. */
class ReservedSlugs
{
    public const LIST = [
        'admin', 'editor', 'category', 'categories', 'articles', 'inbox', 'login', 'logout', 'register', 'dashboard',
        'settings', 'search', 'about', 'contact', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password',
        'email', 'up', 'storage', 'build', 'api', 'issues', 'galleries', 'archives', 'events', 'media', 'newsroom', 'feed', 'sitemap',
    ];

    public static function has(string $slug): bool
    {
        return in_array($slug, self::LIST, true);
    }
}
