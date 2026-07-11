@props(['header' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Evaluasi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas font-body text-ink antialiased">
    <header class="flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-8">
        <a href="{{ route('student.evaluations.index') }}" class="font-display text-lg font-semibold">SIEDU</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted hover:text-ink">{{ auth()->user()->name }} · Keluar</button>
        </form>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-6 pb-24 lg:pb-8">
        @if ($header)
            <h1 class="mb-4 font-display text-2xl font-semibold">{{ $header }}</h1>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-card border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Bottom-nav mobile (§10) --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 flex border-t border-border bg-surface lg:hidden">
        <a href="{{ route('student.evaluations.index') }}"
            class="flex flex-1 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('student.evaluations.*') ? 'text-accent' : 'text-muted' }}">
            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Evaluasi
        </a>
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <button type="submit" class="flex w-full flex-col items-center gap-1 py-2 text-xs text-muted">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form>
    </nav>
</body>
</html>