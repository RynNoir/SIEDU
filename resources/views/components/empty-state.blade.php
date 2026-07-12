@props([
    'message' => 'Belum ada data.',
    'title' => null,
    'icon' => 'inbox',
])

{{-- Empty state (checklist #21 / GUIDELINE §6.7): ikon + pesan informatif + slot CTA. --}}
<div class="flex flex-col items-center justify-center gap-3 rounded-card border border-dashed border-border bg-surface px-6 py-14 text-center">
    @if ($icon)
        <span class="flex size-12 items-center justify-center rounded-full bg-canvas text-muted">
            <x-icon :name="$icon" class="size-6" />
        </span>
    @endif
    @if ($title)
        <p class="font-display text-base font-semibold text-ink">{{ $title }}</p>
    @endif
    <p class="max-w-sm text-sm text-muted">{{ $message }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1">{{ $slot }}</div>
    @endif
</div>
