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

{{--
    hx-boost: header/bottom-nav statis, hanya #app-content (main) yang ditukar htmx
    saat pindah halaman (GUIDELINE §8 motion, tanpa reload penuh).
    hx-sync="this:replace": klik ganda pada elemen yang sama membatalkan request lama,
    mencegah dua respons tumpang tindih memicu transisi ganda (lihat catatan sama di
    app-shell.blade.php).
--}}
<body class="flex min-h-screen flex-col bg-canvas font-body text-ink antialiased"
    hx-boost="true" hx-target="#app-content" hx-select="#app-content" hx-sync="this:replace"
    hx-swap="innerHTML swap:150ms settle:200ms transition:true">
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
                    <form method="POST" action="{{ route('logout') }}" hx-boost="false">
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

    <main id="app-content" class="relative mx-auto w-full max-w-3xl flex-1 px-4 py-6 pb-[calc(6rem+env(safe-area-inset-bottom))] sm:pb-6 lg:pb-8">
        <div class="htmx-indicator absolute inset-x-0 top-0 z-20 h-0.5 bg-accent"></div>

        @if ($header)
            <h1 class="mb-4 font-display text-2xl font-semibold">{{ $header }}</h1>
        @endif

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
        @endif

        {{ $slot }}
    </main>

    {{--
        Bottom-nav mobile (§10). Nav ini di luar #app-content (sengaja, biar tak ikut flicker).
        TIDAK pakai hx-swap-oob: link yang diklik ada DI DALAM <nav> ini, jadi meng-OOB-swap
        seluruh <nav> berarti outerHTML-replace ancestor dari elemen pemicu itu sendiri di
        tengah proses request htmx -- pitfall yang menyebabkan DOM ganda/rusak (lihat catatan
        sama di app-shell.blade.php). Sinkronisasi status aktif lewat JS ringan (app.js) di
        event htmx:afterSettle, cuma toggle class tanpa mengganti elemen DOM.
    --}}
    <nav id="bottom-nav"
        class="fixed inset-x-0 bottom-0 z-20 flex border-t border-border bg-surface pb-[env(safe-area-inset-bottom)] sm:hidden">
        <a href="{{ route('student.evaluations.index') }}" data-nav-link data-nav-match="{{ parse_url(route('student.evaluations.index'), PHP_URL_PATH) }}"
            data-nav-active="text-accent" data-nav-inactive="text-muted"
            @class([
                'flex flex-1 flex-col items-center gap-1 py-2 text-xs',
                'text-accent' => request()->routeIs('student.evaluations.*'),
                'text-muted' => ! request()->routeIs('student.evaluations.*'),
            ])>
            <x-icon name="evaluate" class="size-6" />
            Evaluasi
        </a>
        @if (Route::has('profile.edit'))
            <a href="{{ route('profile.edit') }}" data-nav-link data-nav-match="{{ parse_url(route('profile.edit'), PHP_URL_PATH) }}"
                data-nav-active="text-accent" data-nav-inactive="text-muted"
                @class([
                    'flex flex-1 flex-col items-center gap-1 py-2 text-xs',
                    'text-accent' => request()->routeIs('profile.*'),
                    'text-muted' => ! request()->routeIs('profile.*'),
                ])>
                <x-icon name="profile" class="size-6" />
                Profil
            </a>
        @endif
        <form method="POST" action="{{ route('logout') }}" class="flex-1" hx-boost="false">
            @csrf
            <button type="submit" class="flex w-full flex-col items-center gap-1 py-2 text-xs text-muted">
                <x-icon name="logout" class="size-6" />
                Keluar
            </button>
        </form>
    </nav>
</body>

</html>
