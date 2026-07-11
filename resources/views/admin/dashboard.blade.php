<x-admin-layout header="Dashboard Admin">
    <div class="mb-6">
        <p class="text-ink">Selamat datang, <span class="font-medium">{{ auth()->user()->name }}</span>.</p>
        <p class="mt-0.5 text-sm text-muted">Ringkasan data master sistem evaluasi.</p>
    </div>

    {{-- KPI stat cards (GUIDELINE §13.3) --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat-card label="Program Studi" :value="$studyProgramCount" icon="study-program" />
        <x-stat-card label="Dosen" :value="$lecturerCount" icon="lecturer" />
        <x-stat-card label="Mahasiswa Aktif" :value="$activeStudentCount" icon="student" />
        <x-stat-card label="Kelas" :value="$classGroupCount" icon="class-group" />
        <x-stat-card label="Mata Kuliah" :value="$courseCount" icon="course" />
        <x-stat-card label="Penugasan Dosen" :value="$assignmentCount" icon="assignment" />
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
