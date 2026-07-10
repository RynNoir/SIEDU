@props(['header' => null])

@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.study-programs.index', 'label' => 'Program Studi', 'pattern' => 'admin.study-programs.*'],
        ['route' => 'admin.class-groups.index', 'label' => 'Kelas', 'pattern' => 'admin.class-groups.*'],
        ['route' => 'admin.courses.index', 'label' => 'Mata Kuliah', 'pattern' => 'admin.courses.*'],
        ['route' => 'admin.lecturers.index', 'label' => 'Dosen', 'pattern' => 'admin.lecturers.*'],
        ['route' => 'admin.students.index', 'label' => 'Mahasiswa', 'pattern' => 'admin.students.*'],
        ['route' => 'admin.evaluation-periods.index', 'label' => 'Periode Evaluasi', 'pattern' => 'admin.evaluation-periods.*'],
        ['route' => 'admin.evaluation-questions.index', 'label' => 'Pertanyaan', 'pattern' => 'admin.evaluation-questions.*'],
        ['route' => 'admin.course-class-assignments.index', 'label' => 'Penugasan Dosen', 'pattern' => 'admin.course-class-assignments.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-canvas text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-border bg-surface transition-transform lg:static lg:translate-x-0">
            <div class="flex h-16 items-center border-b border-border px-6">
                <span class="font-display text-lg font-semibold text-ink">SIEDU</span>
                <span class="ml-2 text-xs text-muted">Admin</span>
            </div>
            <nav class="space-y-1 p-4">
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                            class="block rounded-input px-3 py-2 text-sm {{ request()->routeIs($item['pattern']) ? 'bg-accent-soft font-medium text-accent' : 'text-ink hover:bg-canvas' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Konten --}}
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
                    <button type="submit" class="text-sm text-muted hover:text-ink">
                        {{ auth()->user()->name }} · Keluar
                    </button>
                </form>
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