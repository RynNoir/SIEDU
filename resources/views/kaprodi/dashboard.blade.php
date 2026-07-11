<x-kaprodi-layout header="Dashboard Prodi {{ auth()->user()->studyProgram?->code }}">
    {{-- Filter: dosen & periode (§6.6). Filter prodi tak perlu — sudah otomatis. --}}
    <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
        <x-select name="lecturer_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Semua Dosen</option>
            @foreach ($lecturers as $l)
                <option value="{{ $l->id }}" @selected((string) $lecturerId === (string) $l->id)>{{ $l->name }}</option>
            @endforeach
        </x-select>
        <x-select name="period_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Semua Periode</option>
            @foreach ($periods as $p)
                <option value="{{ $p->id }}" @selected((string) $periodId === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </x-select>
    </form>

    @if ($byCourse->isEmpty())
        <x-empty-state message="Belum ada data penugasan/evaluasi untuk prodi ini." />
    @else
        @php $allAssignments = $byCourse->flatten(1); @endphp

        {{-- KPI stat cards (GUIDELINE §13.3) --}}
        <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-card label="Mata Kuliah" :value="$byCourse->count()" icon="course" />
            <x-stat-card label="Dosen Dipantau" :value="$allAssignments->pluck('lecturer_id')->unique()->count()" icon="lecturer" />
            <x-stat-card label="Total Responden" :value="$allAssignments->sum('evaluations_count')" icon="student" />
            <x-stat-card label="Rata-rata Prodi"
                :value="$avgById->isNotEmpty() ? number_format((float) $avgById->avg(), 1) : '–'"
                :rating="$avgById->isNotEmpty()" icon="results" />
        </div>

        {{-- Perbandingan per MK: dosen & kelas paralel berdampingan --}}
        @foreach ($byCourse as $items)
            @php $course = $items->first()->course; @endphp
            <div class="mb-6">
                <h2 class="mb-2 font-medium text-ink">
                    <span class="font-mono text-muted">{{ $course->code }}</span> {{ $course->name }}
                </h2>

                <x-table>
                    <x-slot name="head">
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Dosen</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Periode</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Responden</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Rata-rata</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
                    </x-slot>

                    @foreach ($items as $a)
                        @php $avg = $avgById->get($a->id); @endphp
                        <tr class="hover:bg-accent-soft">
                            <td class="px-4 py-3 text-ink">{{ $a->lecturer->name }}</td>
                            <td class="px-4 py-3 font-mono text-muted">{{ $a->classGroup->class_code }}</td>
                            <td class="px-4 py-3 text-muted">{{ $a->evaluationPeriod->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $a->evaluations_count }}</td>
                            <td class="px-4 py-3 font-mono text-ink">{{ $avg !== null ? number_format((float) $avg, 1) : '–' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button variant="secondary" :href="route('kaprodi.assignments.show', $a)">Detail</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        @endforeach
    @endif
</x-kaprodi-layout>