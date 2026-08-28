@props([
    'title' => null,
    'description' => null,
    'shell' => false,          // app chrome: the persistent subject sidebar
    'workspace' => false,      // the learning session: quieter chrome, deep-work mode
    'marketing' => false,      // landing sections below the fold, and the footer
    'activeTab' => null,
    'bodyClass' => '',
])

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>

    {{-- Zoom is deliberately NOT disabled: pinch-to-zoom is an accessibility
         feature, and 200% browser zoom is a WCAG 2.2 requirement. --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>

    {{-- Pre-paint theme: the frontend defaults to dark (the noir hero); honour a saved choice. --}}
    <script>(function(){try{var t=localStorage.getItem('dth-theme');if(t!=='light')document.documentElement.classList.add('dark');}catch(e){document.documentElement.classList.add('dark');}})();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- One stylesheet request for all three faces + the icon font. `display=swap`
         keeps text painting immediately, which is what LCP measures. --}}
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Space+Grotesk:wght@300..700&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ loadFiles('css/frontend/custom.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <meta name="description" content="{{ $description ?? __('frontend.meta.description') }}"/>
    <meta name="theme-color" content="#101820"/>
    <x-favicons/>

    {{-- Route transitions: enables the shared-element descent where supported,
         and is simply ignored where it isn't. --}}
    <meta name="view-transition" content="same-origin"/>

    <meta property="og:title" content="{{ $title ? $title.' · '.config('app.name') : config('app.name') }}"/>
    <meta property="og:description" content="{{ $description ?? __('frontend.meta.description') }}"/>
    <meta property="og:type" content="website"/>
    <meta property="og:url" content="{{ url()->current() }}"/>
    <meta property="og:image" content="{{ asset('images/logos/main_logo.png') }}"/>
    <meta name="twitter:card" content="summary"/>

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => config('app.url'),
        'logo' => asset('images/logos/main_logo.png'),
        'sameAs' => array_values(array_filter(config('platform.social', []))),
    ], JSON_UNESCAPED_SLASHES) !!}</script>

    <title>{{ $title ? $title.' · '.config('app.name') : config('app.name') }}</title>

    @stack('styles')
</head>
<body
    @class([
        'min-h-screen bg-background text-foreground antialiased',
        'flex flex-col' => ! $shell,
        $bodyClass,
    ])
    @if ($workspace) data-workspace @endif
    @if ($activeTab) data-active-tab="{{ $activeTab }}" @endif
    data-focus-on-label="{{ __('frontend.chat.focus-on') }}"
    data-focus-off-label="{{ __('frontend.chat.focus-off') }}"
>
    {{-- Ambient noir backdrop — decorative, depth-aware, suspended during study. --}}
    <div class="dth-atmosphere" aria-hidden="true"></div>

    {{-- Keyboard users land here first: one key to skip the chrome. --}}
    <a href="#dth-main"
       class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-xl focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-primary-foreground">
        {{ __('frontend.general.skip-to-content') }}
    </a>

    {{-- Single polite live region for every status announcement (streaming,
         grading, milestones), so assistive tech gets one predictable channel. --}}
    <div id="dth-live" class="sr-only" role="status" aria-live="polite" aria-atomic="true"></div>

    @if ($shell)
        {{-- App chrome. The sidebar is the navigation, so the bar above the
             content carries only account controls — one place per job. --}}
        <x-frontend.sidebar />

        <div class="dth-shell flex min-h-screen min-w-0 flex-col lg:pl-[17.5rem]">
            <x-frontend.navbar shell :peripheral="$workspace" />

            <main id="dth-main" class="relative z-10 flex-1">
                {{ $slot }}
            </main>

            @if ($marketing)
                <x-frontend.footer />
            @endif
        </div>
    @else
        <x-frontend.navbar :peripheral="$workspace" />

        <main id="dth-main" class="relative z-10 flex-1">
            {{ $slot }}
        </main>

        <x-frontend.footer :peripheral="$workspace" />
    @endif

    <x-frontend.flash />

    {{-- No jQuery, no SweetAlert, no input-mask library: nothing on the frontend
         needs them, and a deep-work product should not spend its load budget on
         scripts that never run. --}}
    <script src="{{ loadFiles('js/frontend/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
