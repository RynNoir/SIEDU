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
    {{-- Kesan & Saran anonim (§6.5) --}}
    <x-card class="mt-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Kesan & Saran (Anonim)</h2>
                <p class="mt-0.5 text-xs text-muted">Ditampilkan tanpa identitas mahasiswa.</p>
            </div>

            @if ($respondents >= $threshold)
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="period_id" value="{{ request('period_id') }}">
                    <x-select name="rating" class="w-auto" onchange="this.form.submit()">
                        <option value="">Semua Rating</option>
                        <option value="high" @selected($ratingFilter === 'high')>Tinggi (≥ 4)</option>
                        <option value="mid" @selected($ratingFilter === 'mid')>Sedang (3–3.9)</option>
                        <option value="low" @selected($ratingFilter === 'low')>Rendah (&lt; 3)</option>
                    </x-select>
                </form>
            @endif
        </div>

        @if ($respondents < $threshold)
            <x-empty-state message="Kesan & saran akan tampil setelah minimal {{ $threshold }} mahasiswa mengisi evaluasi untuk kelas ini." />
        @elseif ($impressions->isEmpty())
            <x-empty-state message="Tidak ada kesan & saran untuk filter ini." />
        @else
            <div class="space-y-3">
                @foreach ($impressions as $imp)
                    <div class="rounded-card border border-border p-4">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <x-rating-display :score="$imp->avg_rating" />
                            <span class="rounded-full bg-muted/15 px-2 py-0.5 text-xs text-muted">Anonim</span>
                        </div>

                        @if ($imp->impression_text)
                            <div class="mt-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted">Kesan</p>
                                <p class="mt-0.5 text-sm text-ink">{{ $imp->impression_text }}</p>
                            </div>
                        @endif
                        @if ($imp->suggestion_text)
                            <div class="mt-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted">Saran</p>
                                <p class="mt-0.5 text-sm text-ink">{{ $imp->suggestion_text }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</x-lecturer-layout>