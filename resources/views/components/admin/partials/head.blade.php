@props(['title' => null])

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="{{ __('admin/frontend.layout.meta-description') }}">
<meta name="author" content="{{ config('app.name') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://code.jquery.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=JetBrains+Mono:ital,wght@0,100..800;1,100..800&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
@vite('resources/css/app.css')
<link rel="stylesheet" href="{{ loadFiles('css/admin/custom.css') }}">

<meta name="csrf-token" content="{{ csrf_token() }}"/>
<x-favicons/>

{{-- Apply saved theme + sidebar state before paint to avoid a flash of the wrong UI. --}}
<script>
    if (localStorage.getItem('admin-theme') === 'dark' ||
        (!('admin-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
    if (localStorage.getItem('admin-sidebar') === 'collapsed') {
        document.documentElement.classList.add('nav-collapsed');
    }
</script>
