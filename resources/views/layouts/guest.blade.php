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
    {{-- Split edge-to-edge: form sempit kiri, ilustrasi lebar kanan (GUIDELINE §13.7) --}}
    <div class="grid min-h-screen lg:grid-cols-[440px_1fr]">
        <div class="flex flex-col justify-center px-6 py-10 sm:px-10 lg:px-14">
            <div class="mx-auto w-full max-w-sm">
                <a href="/" class="mb-10 inline-flex items-center gap-2">
                    <span class="font-display text-2xl leading-none text-rating">⬥</span>
                    <span class="font-display text-lg font-semibold text-ink">SIEDU</span>
                </a>

                {{ $slot }}
            </div>
        </div>

        <x-auth-illustration />
    </div>
</body>

</html>
