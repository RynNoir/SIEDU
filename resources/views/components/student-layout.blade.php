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

        {{-- Avatar dropdown akun (GUIDELINE §13.2), disembunyikan di mobile (pakai bottom-nav) --}}
        <div class="hidden sm:block">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="flex items-center gap-2 rounded-full p-0.5 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2" aria-label="Menu akun">
                        <x-avatar :name="auth()->user()->name" size="sm" />
                        <span class="text-sm text-ink">{{ auth()->user()->name }}</span>
                    </button>
                </x-slot>
                <x-slot name="content">
                    @if (Route::has('profile.edit'))
                        <x-dropdown-link :href="route('profile.edit')">
                            <span class="flex items-center gap-2"><x-icon name="profile" class="size-4 text-muted" /> Profil</span>
                        </x-dropdown-link>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            <span class="flex items-center gap-2"><x-icon name="logout" class="size-4 text-muted" /> Keluar</span>
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
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
    <nav class="fixed inset-x-0 bottom-0 z-20 flex border-t border-border bg-surface sm:hidden">
        <a href="{{ route('student.evaluations.index') }}"
            @class([
                'flex flex-1 flex-col items-center gap-1 py-2 text-xs',
                'text-accent' => request()->routeIs('student.evaluations.*'),
                'text-muted' => ! request()->routeIs('student.evaluations.*'),
            ])>
            <x-icon name="evaluate" class="size-6" />
            Evaluasi
        </a>
        @if (Route::has('profile.edit'))
            <a href="{{ route('profile.edit') }}"
                @class([
                    'flex flex-1 flex-col items-center gap-1 py-2 text-xs',
                    'text-accent' => request()->routeIs('profile.*'),
                    'text-muted' => ! request()->routeIs('profile.*'),
                ])>
                <x-icon name="profile" class="size-6" />
                Profil
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <button type="submit" class="flex w-full flex-col items-center gap-1 py-2 text-xs text-muted">
                <x-icon name="logout" class="size-6" />
                Keluar
            </button>
        </form>
    </nav>
</body>

</html>
