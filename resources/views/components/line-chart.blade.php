@props([
    'points' => [],
    'labels' => [],
    'color' => 'var(--color-accent)',
    'height' => 200,
])

@php
    // Chart SVG statis tanpa library (GUIDELINE: opsi A). Skala nilai ke viewBox 0-100.
    $values = array_values($points);
    $count = count($values);
    $max = $count ? max(max($values), 1) : 1;
    $min = $count ? min(0, min($values)) : 0;
    $range = max($max - $min, 1);

    $coords = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * 100 : 50;
        $y = 100 - (($v - $min) / $range) * 100;
        $coords[] = [$x, $y];
    }

    $polyline = implode(' ', array_map(fn ($c) => $c[0].','.$c[1], $coords));
    $areaPath = $count
        ? 'M0,100 L'.implode(' ', array_map(fn ($c) => $c[0].','.$c[1], $coords)).' L100,100 Z'
        : '';

    // Tampilkan maksimal ~6 label sumbu-x agar tidak berdesakan (selalu sertakan label terakhir).
    $labelStep = $count > 0 ? max(1, (int) ceil($count / 6)) : 1;
    $visibleLabels = [];
    foreach ($labels as $i => $label) {
        if ($i % $labelStep === 0 || $i === $count - 1) {
            $visibleLabels[] = ['x' => $coords[$i][0] ?? 0, 'text' => $label];
        }
    }
@endphp

<div {{ $attributes }}>
    @if ($count === 0)
        <p class="text-sm text-muted">Belum ada data.</p>
    @else
        <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="height: {{ $height }}px; width: 100%;" aria-hidden="true">
            <path d="{{ $areaPath }}" fill="{{ $color }}" fill-opacity="0.12" />
            <polyline points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="1.5"
                vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" />
            @foreach ($coords as $c)
                <circle cx="{{ $c[0] }}" cy="{{ $c[1] }}" r="1.2" fill="{{ $color }}" vector-effect="non-scaling-stroke" />
            @endforeach
        </svg>

        @if (count($labels) === $count && $count > 0)
            <div class="relative mt-2 h-4 text-xs text-muted">
                @foreach ($visibleLabels as $l)
                    <span class="absolute -translate-x-1/2" style="left: {{ $l['x'] }}%">{{ $l['text'] }}</span>
                @endforeach
            </div>
        @endif
    @endif
</div>
