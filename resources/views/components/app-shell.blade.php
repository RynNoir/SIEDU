@props([
    'titleSuffix' => '',
    'roleLabel' => '',
    'navItems' => [],
    'homeRoute' => '#',
    'header' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }}{{ $titleSuffix ? ' — '.$titleSuffix : '' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-body bg-canvas text-ink antialiased">
    {{--
        hx-boost: semua <a>/<form> di dalam sini otomatis jadi fetch AJAX (htmx),
        hanya #app-content yang ditukar -- sidebar & topbar tetap diam saat pindah menu
        atau submit filter (GUIDELINE §8 motion, tanpa reload penuh).
    --}}
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex"
        hx-boost="true" hx-target="#app-content" hx-select="#app-content"
        hx-swap="innerHTML swap:150ms settle:200ms transition:true">
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        {{-- Sidebar mengambang: shadow + radius besar, bukan border (gaya Elegent) --}}
        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex w-64 transform flex-col bg-surface transition-transform
                   lg:sticky lg:top-6 lg:my-6 lg:ml-6 lg:h-[calc(100vh-3rem)] lg:w-60 lg:translate-x-0
                   lg:rounded-card lg:shadow-lg">
            <div class="flex h-16 items-center gap-2 px-6">
                <a href="{{ $homeRoute }}" class="font-display text-lg font-semibold text-ink">SIEDU</a>
                @if ($roleLabel)
                    <span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent">{{ $roleLabel }}</span>
                @endif
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto p-4">
                @foreach ($navItems as $item)
                    @if (! isset($item['route']) || Route::has($item['route']))
                        @php $active = request()->routeIs($item['pattern'] ?? $item['route']); @endphp
                        <a href="{{ isset($item['route']) ? route($item['route']) : '#' }}"
                            @class([
                                'flex items-center gap-3 rounded-input px-3 py-2.5 text-sm transition duration-150 ease-out-quart focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2',
                                'bg-accent font-medium text-white' => $active,
                                'text-ink hover:bg-accent-soft' => ! $active,
                            ])>
                            <x-icon :name="$item['icon'] ?? 'dashboard'"
                                @class(['size-5 shrink-0', 'text-white' => $active, 'text-muted' => ! $active]) />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Konten yang ditukar htmx saat navigasi/filter (sidebar & topbar di luar area ini tetap diam) --}}
        <div id="app-content" class="relative flex min-w-0 flex-1 flex-col">
            <div class="htmx-indicator absolute inset-x-0 top-0 z-20 h-0.5 bg-accent"></div>

            <header class="flex h-16 items-center justify-between bg-canvas px-4 lg:px-8 lg:pt-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-muted hover:text-ink lg:hidden" aria-label="Buka menu">
                        <x-icon name="menu" class="size-6" />
                    </button>
                    <div class="font-display text-xl font-semibold">{{ $header ?? '' }}</div>
                </div>

                {{-- Avatar dropdown akun (GUIDELINE §13.2) --}}
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full p-0.5 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2" aria-label="Menu akun">
                            <x-avatar :name="auth()->user()->name" size="sm" />
                            <span class="hidden text-sm text-ink sm:inline">{{ auth()->user()->name }}</span>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-border px-4 py-2">
                            <p class="text-sm font-medium text-ink">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-muted">{{ auth()->user()->email }}</p>
                        </div>
                        @if (Route::has('profile.edit'))
                            <x-dropdown-link :href="route('profile.edit')">
                                <span class="flex items-center gap-2"><x-icon name="profile" class="size-4 text-muted" /> Profil</span>
                            </x-dropdown-link>
                        @endif
                        {{-- hx-boost="false": logout selalu navigasi penuh, bukan swap parsial (halaman login beda struktur). --}}
                        <form method="POST" action="{{ route('logout') }}" hx-boost="false">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                <span class="flex items-center gap-2"><x-icon name="logout" class="size-4 text-muted" /> Keluar</span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </header>

            <main class="p-4 lg:px-8 lg:py-6">
                @if (session('success'))
                    <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
                @endif
                @if (session('error'))
                    <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
