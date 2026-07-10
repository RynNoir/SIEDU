<x-admin-layout header="Program Studi">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $studyPrograms->total() }} program studi</p>
        <x-button :href="route('admin.study-programs.create')">Tambah Prodi</x-button>
    </div>

    @if ($studyPrograms->isEmpty())
        <x-empty-state message="Belum ada program studi. Tambahkan prodi pertama untuk memulai." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Jenjang</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Semester</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($studyPrograms as $prodi)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $prodi->code }}</td>
                    <td class="px-4 py-3 text-ink">{{ $prodi->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $prodi->degree_level->value }}</td>
                    <td class="px-4 py-3 text-muted">{{ $prodi->total_semesters }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.study-programs.edit', $prodi)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.study-programs.destroy', $prodi) }}"
                                onsubmit="return confirm('Hapus prodi {{ $prodi->code }}?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $studyPrograms->links() }}</div>
    @endif
</x-admin-layout>