<x-admin-layout header="Pertanyaan Kuesioner">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $questions->total() }} pertanyaan</p>
        <x-button :href="route('admin.evaluation-questions.create')">Tambah Pertanyaan</x-button>
    </div>

    @if ($questions->isEmpty())
        <x-empty-state message="Belum ada pertanyaan. Tambahkan pertanyaan kuesioner evaluasi." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">#</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kategori</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Pertanyaan</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($questions as $question)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-muted">{{ $question->order_number }}</td>
                    <td class="px-4 py-3 text-ink">{{ $question->category }}</td>
                    <td class="max-w-md px-4 py-3 text-muted">{{ $question->question_text }}</td>
                    <td class="px-4 py-3">
                        @if ($question->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-medium text-success">Aktif</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/15 px-2.5 py-0.5 text-xs font-medium text-muted">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.evaluation-questions.edit', $question)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.evaluation-questions.destroy', $question) }}"
                                onsubmit="return confirm('Hapus pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $questions->links() }}</div>
    @endif
</x-admin-layout>