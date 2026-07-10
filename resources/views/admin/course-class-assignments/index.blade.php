<x-admin-layout header="Penugasan Dosen">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $assignments->total() }} penugasan</p>
        <x-button :href="route('admin.course-class-assignments.create')">Tambah Penugasan</x-button>
    </div>

    @if ($assignments->isEmpty())
        <x-empty-state message="Belum ada penugasan. Assign dosen ke mata kuliah + kelas untuk periode aktif." />
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

        <div class="mt-4">{{ $assignments->links() }}</div>
    @endif
</x-admin-layout>