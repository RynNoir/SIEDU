@props(['message' => 'Belum ada data.'])

<div class="flex flex-col items-center justify-center gap-3 rounded-card border border-dashed border-border bg-surface px-6 py-12 text-center">
    <p class="text-sm text-muted">{{ $message }}</p>
    {{ $slot }}
</div>