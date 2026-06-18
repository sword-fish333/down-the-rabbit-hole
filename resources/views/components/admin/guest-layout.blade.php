@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-admin.partials.head :title="$title" />
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    {{ $slot }}

    <x-admin.flash />
    <x-admin.partials.scripts />
</body>
</html>
