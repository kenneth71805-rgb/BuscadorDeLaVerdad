<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('security.csp');
        $nonce = null;

        if (in_array($mode, ['report-only', 'enforce'], true)) {
            $nonce = base64_encode(random_bytes(16));
            $request->attributes->set('csp_nonce', $nonce); // read by the page template for the inline route script
            Vite::useCspNonce($nonce);
        }

        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $h->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('security.hsts') && $request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($nonce) {
            $policy = "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; "
                ."font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
            $h->set($mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', $policy);
        }

        // Staff areas and account pages should never appear in search results.
        if ($request->user() || $request->is('login', 'forgot-password', 'reset-password/*', 'verify-email*', 'confirm-password', 'settings/*')) {
            $h->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
