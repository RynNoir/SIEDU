<x-student-layout header="Daftar Evaluasi">
    @if (! $period)
        <x-empty-state message="Belum ada periode evaluasi yang dibuka. Silakan kembali saat periode evaluasi aktif." />
    @else
        <p class="mb-4 text-sm text-muted">
            Periode: <span class="font-medium text-ink">{{ $period->name }}</span>
        </p>

        @if ($assignments->isEmpty())
            <x-empty-state message="Tidak ada mata kuliah untuk dievaluasi di kelas Anda pada periode ini." />
        @else
            <div class="space-y-3">
                @foreach ($assignments as $assignment)
                    @php $done = in_array($assignment->id, $doneIds, true); @endphp
                    <div class="flex items-center justify-between gap-4 rounded-card border border-border bg-surface p-4">
                        <div class="min-w-0">
                            {{-- Team teaching: label jelas "MK — Dosen" (PRD §6.3.4) --}}
                            <p class="truncate font-medium text-ink">{{ $assignment->course->name }}</p>
                            <p class="truncate text-sm text-muted">
                                <span class="font-mono">{{ $assignment->course->code }}</span> · {{ $assignment->lecturer->name }}
                            </p>
                        </div>

                        @if ($done)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-medium text-success">
                                <span class="size-1.5 rounded-full bg-current"></span> Sudah diisi
                            </span>
                        @else
                            <x-button class="shrink-0" :href="route('student.evaluations.show', $assignment)">Isi Evaluasi</x-button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-student-layout>