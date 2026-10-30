<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;

class RobotsController extends Controller
{
    /** Politely asks search engines to skip staff and account pages. This is not a security measure: those pages require a login. */
    public function __invoke()
    {
        $disallow = ['/admin', '/editor', '/category', '/articles', '/assignments', '/calendar', '/reports', '/inbox', '/notifications',
            '/newsroom', '/corrections', '/dashboard', '/login', '/forgot-password', '/reset-password', '/settings', '/assistant'];

        $body = "User-agent: *\n".collect($disallow)->map(fn ($p) => "Disallow: {$p}")->implode("\n")."\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
