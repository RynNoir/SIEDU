<x-admin-layout header="Periode Evaluasi">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $periods->total() }} periode</p>
        <x-button :href="route('admin.evaluation-periods.create')">Tambah Periode</x-button>
    </div>

    @if ($periods->isEmpty())
        <x-empty-state message="Belum ada periode evaluasi. Buat periode lalu buka saat evaluasi dimulai." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tahun Ajaran</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Rentang</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($periods as $period)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 text-ink">{{ $period->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $period->academic_year }}</td>
                    <td class="px-4 py-3 text-muted">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3"><x-badge-status :status="$period->status" /></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if ($period->status->value !== 'open')
                                <form method="POST" action="{{ route('admin.evaluation-periods.open', $period) }}"
                                    onsubmit="return confirm('Buka periode ini? Periode open lain akan ditutup.')">
                                    @csrf
                                    <x-button type="submit">Buka</x-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.evaluation-periods.close', $period) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary">Tutup</x-button>
                                </form>
                            @endif
                            <x-button variant="secondary" :href="route('admin.evaluation-periods.edit', $period)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.evaluation-periods.destroy', $period) }}"
                                onsubmit="return confirm('Hapus periode ini?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $periods->links() }}</div>
    @endif
</x-admin-layout>