@props(['score', 'max' => 5])

@php $rounded = (int) round((float) $score); @endphp

<div class="flex items-center gap-1" aria-label="Rata-rata {{ number_format((float) $score, 1) }} dari {{ $max }}">
    @for ($i = 1; $i <= $max; $i++)
        <span class="text-base leading-none {{ $i <= $rounded ? 'text-rating' : 'text-border' }}">⬥</span>
    @endfor
    <span class="ml-1 font-mono text-xs text-muted">{{ number_format((float) $score, 1) }}</span>
</div>