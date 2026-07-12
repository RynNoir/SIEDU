<x-lecturer-layout header="Hasil Evaluasi">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Mata kuliah & kelas yang Anda ampu</p>

        {{-- Filter chip periode (GUIDELINE §6.6) --}}
        <form method="GET" class="flex items-center gap-2">
            <x-select name="period_id" class="w-auto" onchange="this.form.submit()">
                <option value="">Semua Periode</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected((string) $selectedPeriodId === (string) $period->id)>{{ $period->name }}</option>
                @endforeach
            </x-select>
        </form>
    </div>

    @if ($assignments->isEmpty())
        <x-empty-state message="Belum ada mata kuliah yang diampu pada periode ini." />
    @else
        {{-- KPI stat cards (GUIDELINE §13.3) --}}
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <x-stat-card label="MK Diampu" :value="$assignments->count()" icon="course" />
            <x-stat-card label="Total Responden" :value="$assignments->sum('evaluations_count')" icon="student" />
            <x-stat-card label="Kelas" :value="$assignments->pluck('class_group_id')->unique()->count()" icon="class-group" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($assignments as $assignment)
                <a href="{{ route('lecturer.assignments.show', $assignment) }}"
                    class="block rounded-card bg-surface p-4 shadow-md transition duration-150 ease-out-quart hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                    <p class="font-medium text-ink">{{ $assignment->course->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        <span class="font-mono">{{ $assignment->course->code }}</span> · Kelas
                        <span class="font-mono">{{ $assignment->classGroup->class_code }}</span>
                    </p>
                    <p class="mt-3 text-sm text-muted">{{ $assignment->evaluationPeriod->name }}</p>
                    <p class="mt-1 text-sm">
                        <span class="font-display text-lg font-semibold text-ink">{{ $assignment->evaluations_count }}</span>
                        <span class="text-muted">responden</span>
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</x-lecturer-layout>