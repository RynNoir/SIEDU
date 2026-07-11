@props(['label', 'score', 'max' => 5])

@php $pct = $max > 0 ? min(100, ($score / $max) * 100) : 0; @endphp

<div>
    <div class="flex items-center justify-between text-sm">
        <span class="text-ink">{{ $label }}</span>
        <span class="font-mono text-muted">{{ number_format((float) $score, 1) }} / {{ $max }}</span>
    </div>
    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-border">
        <div class="h-full rounded-full bg-rating" style="width: {{ $pct }}%"></div>
    </div>
</div>