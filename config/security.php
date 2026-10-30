<?php

return [
    /*
     | Content-Security-Policy: "off" (default, safe for local development with the Vite dev server),
     | "report-only" (browsers log problems but block nothing; use this first in production),
     | or "enforce". See docs/SECURITY.md.
     */
    'csp' => env('SECURITY_CSP', 'off'),

    // Send Strict-Transport-Security on HTTPS responses. Turn on only once HTTPS works everywhere.
    'hsts' => (bool) env('SECURITY_HSTS', false),

    // Generate https:// links even when the app sits behind a proxy that terminates TLS.
    'force_https' => (bool) env('FORCE_HTTPS', false),

    // Comma-separated proxy addresses to trust (or "*" for all). Needed behind a load balancer or reverse proxy.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Where backup archives are written (outside the web root) and how many are kept.
    'backup_path' => env('BACKUP_PATH', storage_path('app/backups')),
    'backup_keep' => (int) env('BACKUP_KEEP', 14),
];
