<x-lecturer-layout header="Detail Hasil Evaluasi">
    <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>

    <div class="mt-3 mb-6">
        <h1 class="font-display text-2xl font-semibold">{{ $assignment->course->name }}</h1>
        <p class="mt-1 text-sm text-muted">
            <span class="font-mono">{{ $assignment->course->code }}</span> · Kelas
            <span class="font-mono">{{ $assignment->classGroup->class_code }}</span> · {{ $assignment->evaluationPeriod->name }}
        </p>
    </div>

    {{-- Kartu ringkasan (§4.3) --}}
    <div class="grid gap-3 sm:grid-cols-2">
        <x-card>
            <p class="text-sm text-muted">Rata-rata Keseluruhan</p>
            <p class="mt-1 font-display text-4xl font-semibold text-ink">{{ number_format($overallAvg, 1) }}</p>
            <div class="mt-2"><x-rating-display :score="$overallAvg" /></div>
        </x-card>
        <x-card>
            <p class="text-sm text-muted">Responden</p>
            <p class="mt-1 font-display text-4xl font-semibold text-ink">{{ $respondents }}<span class="text-lg text-muted"> / {{ $classSize }}</span></p>
            <p class="mt-2 text-sm text-muted">mahasiswa mengisi evaluasi</p>
        </x-card>
    </div>

    {{-- Skor per kategori (bar proporsional §5) --}}
    <x-card class="mt-4">
        <h2 class="mb-4 text-xs font-semibold uppercase tracking-wide text-muted">Skor Per Kategori</h2>
        @if ($categoryScores->isEmpty())
            <p class="text-sm text-muted">Belum ada data penilaian.</p>
        @else
            <div class="space-y-4">
                @foreach ($categoryScores as $row)
                    <x-score-bar :label="$row->category" :score="$row->avg_rating" />
                @endforeach
            </div>
        @endif
    </x-card>
</x-lecturer-layout>