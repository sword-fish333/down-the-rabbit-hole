<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
    <link rel="stylesheet" href="{{ loadFiles('css/custom.css') }}">

    <!-- END: CSS Assets-->
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <x-favicons/>
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => config('app.url'),
        'logo' => asset('images/logos/full-logo.png'),
        'sameAs' => array_values(array_filter(config('platform.social', []))),
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'url' => config('app.url') . '/contact-us',
        ],
    ], JSON_UNESCAPED_SLASHES) !!}</script>
    <title>{{config('app.name')}}</title>

    @stack('styles')

</head>
<body class="t2d-page bg-gray-50 min-h-screen flex flex-col transition-colors duration-300">
<div id="body-overlay"></div>
{{--@if(!iframeRoute(isset($iframe)))--}}
<!-- Navbar -->
<x-frontend.navbar/>
{{--@endif--}}
<!-- Main Content -->
<main class="flex-1 relative z-[1]">
    {{ $slot }}
</main>
    <!-- Footer -->
    <x-frontend.footer/>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<!-- END: JS Assets-->
<script>

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
</script>
@vite('resources/js/app.js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="{{loadFiles('js/actions.js')}}"></script>
<script src="{{loadFiles('js/expandable_text.js')}}"></script>
{!! $js ??'' !!}
@stack('scripts')
</body>
</html>
