<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', $site->name())</title>

    <meta name="description" content="@yield('meta_description', $site->get('meta_description'))">
    <meta name="robots" content="{{ $site->get('robots', 'index, follow') }}">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $site->name() }}">
    <meta property="og:title" content="@yield('title', $site->name())">
    <meta property="og:description" content="@yield('meta_description', $site->get('meta_description'))">
    <meta property="og:url" content="{{ url()->current() }}">
    @php($ogImage = trim($__env->yieldContent('og_image')) ?: $site->url('og_image'))
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">

    @if ($favicon = $site->url('favicon'))
        <link rel="icon" href="{{ $favicon }}">
    @endif

    {{-- Brand colours come from `settings`, so an editor can rebrand without a rebuild. --}}
    <style>
        :root {
            --brand-primary: {{ $site->get('color_primary', '#0060a8') }};
            --brand-secondary: {{ $site->get('color_secondary', '#17469E') }};
            --brand-accent: {{ $site->get('color_accent', '#189030') }};
            --brand-footer-bg: {{ $site->get('color_footer_bg', '#17469E') }};
        }
    </style>

    {{-- Self-hosted Manrope, preloaded and inlined by the Vite fonts manifest.
         Without this the @theme --font-sans never resolves and the whole site
         silently falls back to the system UI font. --}}
    {{ Vite::fonts() }}

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($analytics = $site->get('analytics_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $analytics }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($analytics));
        </script>
    @endif

    {{-- Editor-managed snippets (verification tags, Tag Manager, etc.) — Appearance → Site settings → SEO. --}}
    @if ($headerScripts = $site->get('header_scripts'))
        {!! $headerScripts !!}
    @endif
</head>
<body class="flex min-h-screen flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:rounded-full focus:bg-sky-600 focus:px-5 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lg">
        Skip to content
    </a>

    @include('partials.header')

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    {{-- Editor-managed snippets (chat widgets, pixels, etc.) — Appearance → Site settings → SEO. --}}
    @if ($footerScripts = $site->get('footer_scripts'))
        {!! $footerScripts !!}
    @endif
</body>
</html>
