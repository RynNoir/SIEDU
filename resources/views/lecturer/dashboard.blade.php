<x-lecturer-layout header="Hasil Evaluasi">
    <p class="mb-4 text-sm text-muted">Mata kuliah & kelas yang Anda ampu</p>

    {{--
        Filter live: hx-target/hx-select "#results" override target #app-content bawaan
        shell -- cuma KPI+daftar yang ditukar, bukan seluruh halaman.
    --}}
    <form method="GET" class="mb-4"
        hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-select name="course_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Mata Kuliah</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected((string) $selectedCourseId === (string) $course->id)>{{ $course->code }} — {{ $course->name }}</option>
                @endforeach
            </x-select>
            <x-select name="class_group_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Kelas</option>
                @foreach ($classGroups as $class)
                    <option value="{{ $class->id }}" @selected((string) $selectedClassGroupId === (string) $class->id)>{{ $class->class_code }}</option>
                @endforeach
            </x-select>
            <x-select name="period_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Periode</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected((string) $selectedPeriodId === (string) $period->id)>{{ $period->name }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['course_id', 'class_group_id', 'period_id']))
                <x-button variant="secondary" :href="route('lecturer.dashboard')">Reset</x-button>
            @endif
        </div>
    </form>

    <div id="results" class="relative">
        <div class="htmx-indicator absolute inset-x-0 top-0 z-10 h-0.5 bg-accent"></div>

        @if ($assignments->isEmpty())
            <x-empty-state message="Tidak ada mata kuliah yang cocok. Coba ubah filter." />
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
    </div>
</x-lecturer-layout>
