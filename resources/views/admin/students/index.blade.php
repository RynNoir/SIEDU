<x-admin-layout header="Mahasiswa">
    {{-- Filter live: target #results (bukan #app-content bawaan shell) -- cuma tabel yang ditukar --}}
    <form method="GET" class="mb-4"
        hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-text-input name="search" placeholder="Cari NIM / nama"
                :value="request('search')" x-on:input.debounce.400ms="$el.form.requestSubmit()" />
            <x-select name="study_program_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Prodi</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}" @selected(request('study_program_id') == $prodi->id)>{{ $prodi->code }}</option>
                @endforeach
            </x-select>
            <x-select name="class_group_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Kelas</option>
                @foreach ($classGroups as $class)
                    <option value="{{ $class->id }}" @selected(request('class_group_id') == $class->id)>{{ $class->class_code }}</option>
                @endforeach
            </x-select>
            <x-select name="status" onchange="this.form.requestSubmit()">
                <option value="">Semua Status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['search', 'study_program_id', 'class_group_id', 'status']))
                <x-button variant="secondary" :href="route('admin.students.index')">Reset</x-button>
            @endif
        </div>
    </form>

    <div id="results" class="relative">
        <div class="htmx-indicator absolute inset-x-0 top-0 z-10 h-0.5 bg-accent"></div>

        <div class="mb-4 flex items-center justify-between">
            <p class="text-sm text-muted">{{ $students->total() }} mahasiswa</p>
            <x-button :href="route('admin.students.create')">Tambah Mahasiswa</x-button>
        </div>

        @if ($students->isEmpty())
            <x-empty-state message="Tidak ada mahasiswa yang cocok. Coba ubah filter atau tambahkan mahasiswa baru." />
        @else
            <x-table>
                <x-slot name="head">
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIM</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Sem</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
                </x-slot>

                @foreach ($students as $student)
                    <tr class="hover:bg-accent-soft">
                        <td class="px-4 py-3 font-mono text-ink">{{ $student->nim }}</td>
                        <td class="px-4 py-3 text-ink">{{ $student->name }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $student->classGroup->class_code }}</td>
                        <td class="px-4 py-3 text-muted">{{ $student->current_semester }}</td>
                        <td class="px-4 py-3"><x-badge-status :status="$student->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <x-button variant="secondary" :href="route('admin.students.edit', $student)">Edit</x-button>
                                <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                    onsubmit="return confirm('Hapus mahasiswa {{ $student->nim }} beserta akunnya?')">
                                    @csrf @method('DELETE')
                                    <x-button variant="destructive">Hapus</x-button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4" hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">{{ $students->links() }}</div>
        @endif
    </div>
</x-admin-layout>
