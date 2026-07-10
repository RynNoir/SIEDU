@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-input px-5 py-2.5 text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2';
    $variants = [
        'primary' => 'bg-accent text-white hover:brightness-95',
        'secondary' => 'border border-border text-ink hover:bg-accent-soft',
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