<x-admin-layout header="Dosen">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $lecturers->total() }} dosen</p>
        <x-button :href="route('admin.lecturers.create')">Tambah Dosen</x-button>
    </div>

    {{-- Filter live: dropdown auto-submit, cari didebounce (GUIDELINE §6.6) --}}
    <form method="GET" class="mb-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-text-input name="search" placeholder="Cari NIP / nama / email"
                :value="request('search')" x-on:input.debounce.400ms="$el.form.requestSubmit()" />
            <x-select name="study_program_id" onchange="this.form.requestSubmit()">
                <option value="">Semua Prodi</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}" @selected(request('study_program_id') == $prodi->id)>{{ $prodi->code }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mt-2 flex items-center gap-2">
            <button type="submit" class="sr-only">Filter</button>
            @if (request()->hasAny(['search', 'study_program_id']))
                <x-button variant="secondary" :href="route('admin.lecturers.index')">Reset</x-button>
            @endif
        </div>
    </form>

    @if ($lecturers->isEmpty())
        <x-empty-state message="Tidak ada dosen yang cocok. Coba ubah filter atau tambahkan dosen baru." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIP</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Email</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($lecturers as $lecturer)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $lecturer->nip }}</td>
                    <td class="px-4 py-3 text-ink">{{ $lecturer->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $lecturer->user->email }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $lecturer->studyProgram->code }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.lecturers.edit', $lecturer)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.lecturers.destroy', $lecturer) }}"
                                onsubmit="return confirm('Hapus dosen {{ $lecturer->name }} beserta akunnya?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $lecturers->links() }}</div>
    @endif
</x-admin-layout>