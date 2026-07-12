@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-input px-5 py-2.5 text-sm font-medium transition duration-150 ease-out-quart active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 disabled:pointer-events-none disabled:active:scale-100';
    $variants = [
        'primary' => 'bg-accent text-white hover:brightness-110 hover:shadow-md',
        'secondary' => 'border border-border text-ink hover:bg-accent-soft hover:border-accent/40',
        'destructive' => 'border border-danger text-danger hover:bg-danger/10',
        'disabled' => 'bg-border text-muted cursor-not-allowed',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href && $variant !== 'disabled')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button @if ($variant === 'disabled') disabled @endif {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
