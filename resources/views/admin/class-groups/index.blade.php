<x-admin-layout header="Kelas">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $classGroups->total() }} kelas</p>
        <x-button :href="route('admin.class-groups.create')">Tambah Kelas</x-button>
    </div>

    @if ($classGroups->isEmpty())
        <x-empty-state message="Belum ada kelas. Tambahkan kelas per prodi per tahun ajaran." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode Kelas</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tahun Ajaran</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tingkat</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kapasitas</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($classGroups as $class)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $class->class_code }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $class->studyProgram->code }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $class->academic_year }}</td>
                    <td class="px-4 py-3 text-muted">Tahun {{ $class->year_level }}</td>
                    <td class="px-4 py-3 text-muted">{{ $class->capacity }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.class-groups.edit', $class)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.class-groups.destroy', $class) }}"
                                onsubmit="return confirm('Hapus kelas {{ $class->class_code }}?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $classGroups->links() }}</div>
    @endif
</x-admin-layout>