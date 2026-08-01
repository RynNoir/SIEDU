<x-lecturer-layout header="Detail Hasil Evaluasi">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>
        {{-- hx-boost="false": unduhan harus jadi navigasi biasa (lihat catatan di dashboard). --}}
        <x-button variant="secondary" hx-boost="false"
            :href="route('lecturer.assignments.export', $assignment)">Unduh CSV</x-button>
    </div>

    @include('partials.assignment-result')
</x-lecturer-layout>