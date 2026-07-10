<x-admin-layout header="Mata Kuliah">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $courses->total() }} mata kuliah</p>
        <x-button :href="route('admin.courses.create')">Tambah Mata Kuliah</x-button>
    </div>

    @if ($courses->isEmpty())
        <x-empty-state message="Belum ada mata kuliah. Tambahkan kurikulum paket per prodi." />
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