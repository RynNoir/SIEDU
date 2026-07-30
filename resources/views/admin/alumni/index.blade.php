<x-admin-layout header="Alumni">
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
            <x-select name="enrollment_year" onchange="this.form.requestSubmit()">
                <option value="">Semua Angkatan</option>
                @foreach ($enrollmentYears as $year)
                    <option value="{{ $year }}" @selected(request('enrollment_year') == $year)>Angkatan {{ $year }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['search', 'study_program_id', 'enrollment_year']))
                <x-button variant="secondary" :href="route('admin.alumni.index')">Reset</x-button>
            @endif
        </div>
    </form>

    <div id="results" class="relative">
        <div class="htmx-indicator absolute inset-x-0 top-0 z-10 h-0.5 bg-accent"></div>

        <p class="mb-4 text-sm text-muted">{{ $alumni->total() }} alumni</p>

        @if ($alumni->isEmpty())
            <x-empty-state message="Belum ada alumni. Mahasiswa otomatis menjadi alumni setelah menuntaskan semester terakhirnya lewat Promosi Semester." />
        @else
            <x-table>
                <x-slot name="head">
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIM</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Angkatan</th>
                    <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas Terakhir</th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
                </x-slot>

                @foreach ($alumni as $student)
                    <tr class="hover:bg-accent-soft">
                        <td class="px-4 py-3 font-mono text-ink">{{ $student->nim }}</td>
                        <td class="px-4 py-3 text-ink">{{ $student->name }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $student->studyProgram->code }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $student->enrollment_year }}</td>
                        <td class="px-4 py-3 font-mono text-muted">{{ $student->classGroup->class_code }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Escape hatch: kalau ada yang ter-luluskan keliru, statusnya
                                     dikembalikan lewat form edit mahasiswa yang sudah ada. --}}
                                <x-button variant="secondary" :href="route('admin.students.edit', $student)">Edit</x-button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>

            <div class="mt-4" hx-target="#results" hx-select="#results" hx-swap="innerHTML swap:100ms settle:150ms">{{ $alumni->links() }}</div>
        @endif
    </div>
</x-admin-layout>
