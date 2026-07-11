@props(['header' => null])

@php
    $navItems = [
        ['route' => 'lecturer.dashboard', 'label' => 'Hasil Evaluasi', 'pattern' => 'lecturer.*', 'icon' => 'results'],
    ];
@endphp

<x-app-shell title-suffix="Dosen" role-label="Dosen" :nav-items="$navItems"
    :home-route="route('lecturer.dashboard')" :header="$header">
    {{ $slot }}
</x-app-shell>
