@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'caption' => null,
    'rating' => false,
])

{{-- Kartu statistik/KPI (GUIDELINE §13.3). Border 1px sebagai struktur utama. --}}
<div {{ $attributes->merge(['class' => 'rounded-card border border-border bg-surface p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</p>
            <p class="mt-1 flex items-baseline gap-1.5 font-display text-3xl font-semibold text-ink">
                {{ $value }}
                @if ($rating)
                    <span class="text-xl leading-none text-rating">⬥</span>
                @endif
            </p>
            @if ($caption)
                <p class="mt-1 text-xs text-muted">{{ $caption }}</p>
            @endif
        </div>

        @if ($icon)
            <span class="flex size-10 shrink-0 items-center justify-center rounded-input bg-accent-soft text-accent">
                <x-icon :name="$icon" class="size-5" />
            </span>
        @endif
    </div>
</div>
