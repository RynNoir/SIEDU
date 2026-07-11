@props(['header' => null])

@php
    $navItems = [
        ['route' => 'kaprodi.dashboard', 'label' => 'Dashboard Prodi', 'pattern' => 'kaprodi.*', 'icon' => 'results'],
    ];
    $roleLabel = 'Kaprodi'.(auth()->user()->studyProgram?->code ? ' '.auth()->user()->studyProgram->code : '');
@endphp

<x-app-shell title-suffix="Kaprodi" :role-label="$roleLabel" :nav-items="$navItems"
    :home-route="route('kaprodi.dashboard')" :header="$header">
    {{ $slot }}
</x-app-shell>
