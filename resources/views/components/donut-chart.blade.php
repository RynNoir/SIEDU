@props([
    'segments' => [], // [['label' => string, 'value' => number, 'color' => css-color], ...]
])

@php
    // Donut SVG statis tanpa library — teknik r=15.9155 supaya circumference persis 100 (stroke-dasharray % langsung).
    $total = array_sum(array_column($segments, 'value'));
    $radius = 15.9155;
    $offset = 0;
    $arcs = [];
    foreach ($segments as $segment) {
        $pct = $total > 0 ? ($segment['value'] / $total) * 100 : 0;
        $arcs[] = [
            'pct' => $pct,
            'offset' => $offset,
            'color' => $segment['color'],
            'label' => $segment['label'],
            'value' => $segment['value'],
        ];
        $offset += $pct;
    }
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-6 sm:flex-row']) }}>
    @if ($total === 0)
        <p class="text-sm text-muted">Belum ada data.</p>
    @else
        <svg viewBox="0 0 42 42" class="size-40 shrink-0 -rotate-90" aria-hidden="true">
            <circle cx="21" cy="21" r="{{ $radius }}" fill="none" stroke="var(--color-canvas)" stroke-width="7" />
            @foreach ($arcs as $arc)
                <circle cx="21" cy="21" r="{{ $radius }}" fill="none" stroke="{{ $arc['color'] }}" stroke-width="7"
                    stroke-dasharray="{{ $arc['pct'] }} {{ 100 - $arc['pct'] }}"
                    stroke-dashoffset="{{ -$arc['offset'] }}" />
            @endforeach
        </svg>

        <ul class="w-full min-w-0 flex-1 space-y-2">
            @foreach ($arcs as $arc)
                <li class="flex items-center gap-2 text-sm">
                    <span class="size-2.5 shrink-0 rounded-full" style="background-color: {{ $arc['color'] }}"></span>
                    <span class="flex-1 truncate text-ink">{{ $arc['label'] }}</span>
                    <span class="font-mono text-muted">{{ $arc['value'] }}</span>
                    <span class="w-12 text-right font-mono text-xs text-muted">{{ number_format($arc['pct'], 0) }}%</span>
                </li>
            @endforeach
        </ul>
    @endif
</div>
