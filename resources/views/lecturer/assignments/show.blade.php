<x-lecturer-layout header="Detail Hasil Evaluasi">
    <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>

    @include('partials.assignment-result')
</x-lecturer-layout>