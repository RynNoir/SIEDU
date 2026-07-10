@props(['status'])

@php
    $key = $status instanceof \BackedEnum ? $status->value : $status;
    $map = [
        'aktif' => ['label' => 'Aktif', 'class' => 'bg-success/15 text-success'],
        'cuti' => ['label' => 'Cuti', 'class' => 'bg-warning/15 text-warning'],
        'DO' => ['label' => 'DO', 'class' => 'bg-danger/15 text-danger'],
        'lulus' => ['label' => 'Lulus', 'class' => 'bg-muted/15 text-muted'],
        'draft' => ['label' => 'Draft', 'class' => 'bg-muted/15 text-muted'],
        'open' => ['label' => 'Open', 'class' => 'bg-accent-soft text-accent'],
        'closed' => ['label' => 'Closed', 'class' => 'bg-muted/15 text-muted'],
    ];
    $s = $map[$key] ?? ['label' => ucfirst((string) $key), 'class' => 'bg-muted/15 text-muted'];
@endphp

{{-- Selalu ada label teks (bukan warna saja) demi aksesibilitas GUIDELINE §9 --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium '.$s['class']]) }}>
    <span class="size-1.5 rounded-full bg-current"></span>
    {{ $s['label'] }}
</span>