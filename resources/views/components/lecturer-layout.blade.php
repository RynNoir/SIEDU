@props(['header' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Dosen</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-canvas text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-border bg-surface transition-transform lg:static lg:translate-x-0">
            <div class="flex h-16 items-center border-b border-border px-6">
                <span class="font-display text-lg font-semibold">SIEDU</span>
                <span class="ml-2 text-xs text-muted">Dosen</span>
            </div>
            <nav class="space-y-1 p-4">
                <a href="{{ route('lecturer.dashboard') }}"
                    class="block rounded-input px-3 py-2 text-sm {{ request()->routeIs('lecturer.dashboard') || request()->routeIs('lecturer.assignments.*') ? 'bg-accent-soft font-medium text-accent' : 'text-ink hover:bg-canvas' }}">
                    Hasil Evaluasi
                </a>
            </nav>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-muted lg:hidden" aria-label="Menu">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="font-display text-base font-semibold">{{ $header ?? '' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-muted hover:text-ink">{{ auth()->user()->name }} · Keluar</button>
                </form>
            </header>

            <main class="p-4 lg:p-6">{{ $slot }}</main>
        </div>
    </div>
</body>
</html>