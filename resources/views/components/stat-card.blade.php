@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'caption' => null,
    'rating' => false,
])

{{-- Kartu statistik/KPI ala SaleInfo Elegent: ikon besar kiri, konten kanan, shadow+radius besar. --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-4 rounded-card bg-surface p-5 shadow-md']) }}>
    @if ($icon)
        <span class="flex size-14 shrink-0 items-center justify-center rounded-card bg-accent-soft text-accent">
            <x-icon :name="$icon" class="size-7" />
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</p>
        <p class="mt-1 flex items-baseline gap-1.5 font-display text-2xl font-semibold text-ink">
            {{ $value }}
            @if ($rating)
                <span class="text-lg leading-none text-rating">⬥</span>
            @endif
        </p>
        @if ($caption)
            <p class="mt-0.5 text-xs text-muted">{{ $caption }}</p>
        @endif
    </div>
</div>
