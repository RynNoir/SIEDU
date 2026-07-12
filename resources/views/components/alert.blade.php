@props([
    'type' => 'info',
    'dismissible' => true,
])

@php
    // Alert feedback (checklist #13/#23). Warna semantik GUIDELINE §2, selalu ada ikon + teks.
    $map = [
        'success' => ['icon' => 'check-circle', 'class' => 'bg-success/10 text-success', 'ring' => 'ring-success/20'],
        'error' => ['icon' => 'x-circle', 'class' => 'bg-danger/10 text-danger', 'ring' => 'ring-danger/20'],
        'warning' => ['icon' => 'warning-triangle', 'class' => 'bg-warning/10 text-warning', 'ring' => 'ring-warning/20'],
        'info' => ['icon' => 'info-circle', 'class' => 'bg-accent-soft text-accent', 'ring' => 'ring-accent/20'],
    ];
    $a = $map[$type] ?? $map['info'];
@endphp

<div x-data="{ show: true }" x-show="show" x-cloak
    x-transition:enter="transition ease-out-quart duration-200"
    x-transition:enter-start="opacity-0 -translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-out-quart duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    role="alert"
    {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-card px-4 py-3 text-sm ring-1 ring-inset {$a['class']} {$a['ring']}"]) }}>
    <x-icon :name="$a['icon']" class="mt-0.5 size-5 shrink-0" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" @click="show = false" class="-mr-1 shrink-0 rounded-md p-0.5 opacity-70 transition hover:opacity-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current" aria-label="Tutup">
            <x-icon name="close" class="size-4" />
        </button>
    @endif
</div>
