@php
    // Profil diakses semua role — pakai shell sesuai role login agar navigasi tetap konsisten (GUIDELINE §13).
    $layout = match (true) {
        auth()->user()->isAdmin() => 'admin-layout',
        auth()->user()->isLecturer() => 'lecturer-layout',
        auth()->user()->isKaprodi() => 'kaprodi-layout',
        default => 'student-layout',
    };
@endphp

<x-dynamic-component :component="$layout" header="Profil Saya">
    <div class="max-w-2xl space-y-4">
        <x-card>
            @include('profile.partials.update-profile-information-form')
        </x-card>

        <x-card>
            @include('profile.partials.update-password-form')
        </x-card>
    </div>
</x-dynamic-component>
