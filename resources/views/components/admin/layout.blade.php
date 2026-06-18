@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-admin.partials.head :title="$title" />
</head>
<body class="min-h-screen bg-background text-foreground antialiased">
    <div class="flex min-h-screen">
        <x-admin.partials.sidebar />

        {{-- Mobile sidebar backdrop --}}
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-ink-950/50 backdrop-blur-sm lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col">
            <x-admin.partials.topbar :title="$title ?? ''" />

            <main class="flex-1 p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-admin.flash />
    <x-admin.partials.scripts />
</body>
</html>
