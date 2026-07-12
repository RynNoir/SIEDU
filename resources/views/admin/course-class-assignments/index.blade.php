<x-admin-layout header="Penugasan Dosen">
    {{-- Filter live: target #results (bukan #app-content bawaan shell) -- cuma tabel yang ditukar --}}
    <form method="GET" class="mb-4"
        hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-select name="course_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Mata Kuliah</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->name }}</option>
                @endforeach
            </x-select>
            <x-select name="lecturer_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Dosen</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected(request('lecturer_id') == $lecturer->id)>{{ $lecturer->name }}</option>
                @endforeach
            </x-select>
            <x-select name="class_group_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Kelas</option>
                @foreach ($classGroups as $class)
                    <option value="{{ $class->id }}" @selected(request('class_group_id') == $class->id)>{{ $class->class_code }}</option>
                @endforeach
            </x-select>
            <x-select name="evaluation_period_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Periode</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected(request('evaluation_period_id') == $period->id)>{{ $period->name }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['course_id', 'lecturer_id', 'class_group_id', 'evaluation_period_id']))
                <x-button variant="secondary" :href="route('admin.course-class-assignments.index')">Reset</x-button>
            @endif
        </div>
    </form>

    <div id="results" class="relative">
        <div class="htmx-indicator absolute inset-x-0 top-0 z-10 h-0.5 bg-accent"></div>

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-muted">{{ $assignments->total() }} penugasan</p>
            <x-button :href="route('admin.course-class-assignments.create')">Tambah Penugasan</x-button>
        </div>

        @if ($assignments->isEmpty())
            <x-empty-state message="Tidak ada penugasan yang cocok. Coba ubah filter atau tambahkan penugasan baru." />
        @else
            <x-table>
                <x-slot name="head">
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Mata Kuliah</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Dosen</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Periode</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
                </x-slot>

                @foreach ($assignments as $a)
                    <tr class="hover:bg-accent-soft">
                        <td class="px-4 py-3 text-ink">
                            <span class="font-mono text-muted">{{ $a->course->code }}</span> {{ $a->course->name }}
                        </td>
                        <td class="px-4 py-3 text-ink">{{ $a->lecturer->name }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $a->classGroup->class_code }}</td>
                        <td class="px-4 py-3 text-muted">{{ $a->evaluationPeriod->name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <x-button variant="secondary" :href="route('admin.course-class-assignments.edit', $a)">Edit</x-button>
                                <form method="POST" action="{{ route('admin.course-class-assignments.destroy', $a) }}"
                                    onsubmit="return confirm('Hapus penugasan ini?')">
                                    @csrf @method('DELETE')
                                    <x-button variant="destructive">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4" hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">{{ $assignments->links() }}</div>
        @endif
    </div>
</x-admin-layout>
