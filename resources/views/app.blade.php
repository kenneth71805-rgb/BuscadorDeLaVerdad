<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($meta = $page['props']['meta'] ?? null)
        {{-- Written on the server so search engines and link previews (Facebook, Messenger, WhatsApp) can read them without running JavaScript. --}}
        <title inertia>{{ $meta ? $meta['title'].' - '.config('app.name') : config('app.name', 'Laravel') }}</title>
        @if ($meta)
            <meta name="description" content="{{ $meta['description'] }}">
            <link rel="canonical" href="{{ $meta['canonical'] }}">
            @if (! empty($meta['robots']))
                <meta name="robots" content="{{ $meta['robots'] }}">
            @endif
            <meta property="og:site_name" content="{{ config('app.name') }}">
            <meta property="og:type" content="{{ $meta['type'] }}">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            <meta property="og:url" content="{{ $meta['canonical'] }}">
            @if (! empty($meta['image']))
                <meta property="og:image" content="{{ $meta['image'] }}">
                <meta name="twitter:card" content="summary_large_image">
            @else
                <meta name="twitter:card" content="summary">
            @endif
            @if (! empty($meta['published_time']))
                <meta property="article:published_time" content="{{ $meta['published_time'] }}">
            @endif
            @if (! empty($meta['modified_time']))
                <meta property="article:modified_time" content="{{ $meta['modified_time'] }}">
            @endif
            @if (! empty($meta['section']))
                <meta property="article:section" content="{{ $meta['section'] }}">
            @endif
            @if (! empty($meta['jsonld']))
                {{-- The HEX flags make it impossible for story text to close this tag early. --}}
                <script type="application/ld+json">{!! json_encode($meta['jsonld'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            @endif
        @endif

        @routes(false, request()->attributes->get('csp_nonce'))
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
