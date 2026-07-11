<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SIEDU') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-body text-ink antialiased">
    <div class="min-h-screen bg-canvas lg:grid lg:grid-cols-2">
        {{-- Panel identitas (GUIDELINE §13.7) --}}
        <div class="flex flex-col justify-between bg-ink px-6 py-8 text-canvas lg:px-12 lg:py-12">
            <a href="/" class="font-display text-xl font-semibold text-white">SIEDU</a>

            <div class="hidden lg:block">
                <h1 class="font-display text-3xl font-semibold leading-tight text-white">
                    Sistem Evaluasi<br>Dosen Terpadu
                </h1>
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-canvas/70">
                    Instrumen evaluasi dosen yang presisi dan terukur untuk Jurusan Teknologi Informasi —
                    penilaian sebagai pembacaan data, bukan rating konsumen.
                </p>
                <div class="mt-6 flex items-center gap-1.5 text-rating" aria-hidden="true">
                    <span>⬥</span><span>⬥</span><span>⬥</span><span>⬥</span><span class="text-canvas/30">⬥</span>
                </div>
            </div>

            <p class="hidden text-xs text-canvas/50 lg:block">
                Politeknik Negeri Padang · Jurusan Teknologi Informasi
            </p>
        </div>

        {{-- Kolom form --}}
        <div class="flex items-center justify-center px-6 py-10 lg:px-12">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>

</html>
