<x-admin-layout header="Mata Kuliah">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $courses->total() }} mata kuliah</p>
        <x-button :href="route('admin.courses.create')">Tambah Mata Kuliah</x-button>
    </div>

    {{-- Filter live: dropdown auto-submit, cari didebounce (GUIDELINE §6.6) --}}
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-text-input name="search" placeholder="Cari kode / nama mata kuliah"
                :value="request('search')" x-on:input.debounce.400ms="$el.form.submit()" />
            <x-select name="study_program_id" onchange="this.form.submit()">
                <option value="">Semua Prodi</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}" @selected(request('study_program_id') == $prodi->id)>{{ $prodi->code }}</option>
                @endforeach
            </x-select>
            <x-select name="semester" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                @foreach ($semesters as $sem)
                    <option value="{{ $sem }}" @selected((string) request('semester') === (string) $sem)>Semester {{ $sem }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['search', 'study_program_id', 'semester']))
                <x-button variant="secondary" :href="route('admin.courses.index')">Reset</x-button>
            @endif
        </div>
    </form>

    @if ($courses->isEmpty())
        <x-empty-state message="Tidak ada mata kuliah yang cocok. Coba ubah filter atau tambahkan mata kuliah baru." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Sem</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">SKS</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($courses as $course)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $course->code }}</td>
                    <td class="px-4 py-3 text-ink">{{ $course->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $course->studyProgram->code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $course->semester }}</td>
                    <td class="px-4 py-3 text-muted">{{ $course->credit_hours }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.courses.edit', $course)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                onsubmit="return confirm('Hapus mata kuliah {{ $course->code }}?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $courses->links() }}</div>
    @endif
</x-admin-layout>