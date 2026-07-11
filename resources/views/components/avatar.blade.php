@props(['name' => '', 'size' => 'md'])

@php
    // Avatar inisial (GUIDELINE §13.4) — accent-soft + inisial accent, tanpa foto.
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');

    $sizes = [
        'sm' => 'size-8 text-xs',
        'md' => 'size-10 text-sm',
        'lg' => 'size-12 text-base',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full bg-accent-soft font-display font-semibold text-accent $sizeClass"]) }}>
    {{ $initials ?: '?' }}
</span>
