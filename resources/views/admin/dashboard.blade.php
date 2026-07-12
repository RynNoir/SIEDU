<x-admin-layout header="Dashboard Admin">
    <div class="mb-6">
        <p class="text-ink">Selamat datang, <span class="font-medium">{{ auth()->user()->name }}</span>.</p>
        <p class="mt-0.5 text-sm text-muted">Ringkasan data master sistem evaluasi.</p>
    </div>

    {{-- KPI stat cards ala SaleInfo Elegent (§13.3) --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat-card label="Program Studi" :value="$studyProgramCount" icon="study-program" />
        <x-stat-card label="Dosen" :value="$lecturerCount" icon="lecturer" />
        <x-stat-card label="Mahasiswa Aktif" :value="$activeStudentCount" icon="student" />
        <x-stat-card label="Kelas" :value="$classGroupCount" icon="class-group" />
        <x-stat-card label="Mata Kuliah" :value="$courseCount" icon="course" />
        <x-stat-card label="Penugasan Dosen" :value="$assignmentCount" icon="assignment" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- Line chart: evaluasi masuk per hari (ala widget Revenue Elegent, tanpa library) --}}
        <x-card class="lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-ink">Evaluasi Masuk</h2>
                <span class="text-xs text-muted">14 hari terakhir</span>
            </div>
            <x-line-chart :points="$dailyEvaluationCounts" :labels="$dailyEvaluationLabels" />
        </x-card>

        {{-- Donut chart: distribusi status mahasiswa (ala widget Website Visitors Elegent) --}}
        <x-card>
            <h2 class="mb-4 font-display text-lg font-semibold text-ink">Status Mahasiswa</h2>
            <x-donut-chart :segments="$studentStatusSegments" />
        </x-card>
    </div>

    {{-- Status periode evaluasi berjalan --}}
    <x-card class="mt-4">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Periode Evaluasi Berjalan</h2>
        @if ($openPeriod)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-display text-lg font-semibold text-ink">{{ $openPeriod->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        <span class="font-mono">{{ $openPeriod->academic_year }}</span> ·
                        {{ $openPeriod->start_date?->translatedFormat('d M Y') }} – {{ $openPeriod->end_date?->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-display text-2xl font-semibold text-ink">{{ $openPeriodEvaluationCount }}</p>
                    <p class="text-xs text-muted">evaluasi masuk</p>
                </div>
            </div>
        @else
            <p class="text-sm text-muted">Tidak ada periode evaluasi yang sedang dibuka.
                <a href="{{ route('admin.evaluation-periods.index') }}" class="text-accent hover:underline">Kelola periode →</a>
            </p>
        @endif
    </x-card>
</x-admin-layout>
