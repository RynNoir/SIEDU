<x-admin-layout header="Dosen">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $lecturers->total() }} dosen</p>
        <x-button :href="route('admin.lecturers.create')">Tambah Dosen</x-button>
    </div>

    @if ($lecturers->isEmpty())
        <x-empty-state message="Belum ada dosen. Tambahkan akun dosen (akan dibuat dengan password default)." />
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