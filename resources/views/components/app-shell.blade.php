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
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        {{-- Sidebar mengambang (GUIDELINE §13.1) --}}
        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex w-64 transform flex-col border-r border-border bg-surface transition-transform lg:static lg:translate-x-0">
            <div class="flex h-16 items-center gap-2 border-b border-border px-6">
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
                                'flex items-center gap-3 rounded-input px-3 py-2 text-sm transition duration-150',
                                'bg-accent-soft font-medium text-accent' => $active,
                                'text-ink hover:bg-canvas' => ! $active,
                            ])>
                            <x-icon :name="$item['icon'] ?? 'dashboard'"
                                @class(['size-5 shrink-0', 'text-accent' => $active, 'text-muted' => ! $active]) />
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Konten --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-muted hover:text-ink lg:hidden" aria-label="Buka menu">
                        <x-icon name="menu" class="size-6" />
                    </button>
                    <div class="font-display text-base font-semibold">{{ $header ?? '' }}</div>
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
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                <span class="flex items-center gap-2"><x-icon name="logout" class="size-4 text-muted" /> Keluar</span>
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </header>

            <main class="p-4 lg:p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-card border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>

</html>
