<x-admin-layout header="Impor Mahasiswa">
    <a href="{{ route('admin.students.index') }}" class="text-sm text-accent hover:underline">← Kembali ke Mahasiswa</a>

    <x-card class="mt-3 max-w-3xl">
        <p class="text-sm leading-relaxed text-muted">
            Tambahkan banyak mahasiswa sekaligus dari satu berkas CSV. Unduh templatnya dulu, isi di Excel,
            lalu unggah ke sini. <span class="font-medium text-ink">Tidak ada data yang tersimpan sebelum kamu mengonfirmasi.</span>
        </p>

        <div class="mt-6 space-y-4">
            {{-- Langkah 1 --}}
            <div class="rounded-input bg-canvas p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-ink">1. Unduh template</p>
                        <p class="mt-0.5 text-xs text-muted">Berisi baris judul kolom &amp; satu baris contoh.</p>
                    </div>
                    {{-- hx-boost="false": unduhan harus jadi navigasi biasa, bukan swap parsial. --}}
                    <x-button variant="secondary" hx-boost="false"
                        :href="route('admin.students.import.template')">Unduh Template</x-button>
                </div>

                <div class="mt-3 overflow-x-auto">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted">Kolom yang diharapkan</p>
                    <code class="block whitespace-nowrap rounded-input bg-surface px-3 py-2 font-mono text-xs text-ink">{{ implode(' ; ', \App\Services\StudentImportService::COLUMNS) }}</code>
                    <p class="mt-2 text-xs leading-relaxed text-muted">
                        <span class="font-medium text-ink">kode_prodi</span> &amp;
                        <span class="font-medium text-ink">kode_kelas</span> diisi kodenya (mis.
                        <span class="font-mono">MI</span> dan <span class="font-mono">MI1A</span>), bukan angka.
                        <span class="font-medium text-ink">status</span> boleh dikosongkan — dianggap
                        <span class="font-mono">aktif</span>.
                    </p>
                </div>
            </div>

            {{-- Langkah 2 --}}
            <form method="POST" action="{{ route('admin.students.import.preview') }}"
                enctype="multipart/form-data" class="rounded-input bg-canvas p-4" hx-boost="false">
                @csrf
                <p class="text-sm font-medium text-ink">2. Unggah berkas yang sudah diisi</p>
                <p class="mt-0.5 text-xs text-muted">Format CSV, maksimal 2 MB.</p>

                <input type="file" name="file" accept=".csv,text/csv" required
                    class="mt-3 block w-full rounded-input border border-border bg-surface text-sm text-ink shadow-sm file:mr-3 file:border-0 file:bg-accent-soft file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-accent hover:file:brightness-95 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent">
                <x-input-error :messages="$errors->get('file')" class="mt-1" />

                <x-button type="submit" class="mt-3">Periksa Berkas</x-button>
            </form>
        </div>
    </x-card>

    @if ($preview)
        <x-card class="mt-4 max-w-3xl">
            @if ($preview['missingColumns'])
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Berkas belum sesuai</h2>
                <x-alert type="error" :dismissible="false" class="mt-3">
                    Kolom berikut tidak ditemukan:
                    <span class="font-mono">{{ implode(', ', $preview['missingColumns']) }}</span>.
                    Unduh templatnya dan pastikan baris judul kolom tidak diubah.
                </x-alert>
            @elseif (empty($preview['rows']))
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Pratinjau</h2>
                <x-empty-state message="Berkasnya tidak berisi baris data apa pun." />
            @else
                <div class="mb-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Pratinjau</h2>
                    <p class="mt-1 text-sm text-ink">{{ count($preview['rows']) }} baris terbaca</p>
                    <p class="mt-1 flex flex-wrap items-center gap-3 text-sm">
                        <span class="text-success">✓ {{ $preview['validCount'] }} siap diimpor</span>
                        @if ($preview['invalidCount'] > 0)
                            <span class="text-danger">✗ {{ $preview['invalidCount'] }} bermasalah</span>
                        @endif
                    </p>
                </div>

                <x-table>
                    <x-slot name="head">
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Baris</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIM</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Keterangan</th>
                    </x-slot>

                    @foreach ($preview['rows'] as $row)
                        <tr class="{{ $row['valid'] ? 'hover:bg-accent-soft' : 'bg-danger/5' }}">
                            <td class="px-4 py-3 font-mono text-muted">{{ $row['line'] }}</td>
                            <td class="px-4 py-3 font-mono text-ink">{{ $row['data']['nim'] ?: '—' }}</td>
                            <td class="px-4 py-3 text-ink">{{ $row['data']['nama'] ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-muted">{{ $row['data']['kode_kelas'] ?: '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($row['valid'])
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-medium text-success">
                                        <span class="size-1.5 rounded-full bg-current"></span> Siap
                                    </span>
                                @else
                                    <ul class="space-y-1 text-xs leading-relaxed text-danger">
                                        @foreach ($row['errors'] as $error)
                                            <li>• {{ $error }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                @if ($preview['validCount'] > 0)
                    <form method="POST" action="{{ route('admin.students.import.store') }}" class="mt-4"
                        onsubmit="return confirm('Impor {{ $preview['validCount'] }} mahasiswa? Akun mereka akan langsung dibuat.')">
                        @csrf
                        <x-button type="submit">Impor {{ $preview['validCount'] }} Baris Valid</x-button>
                        <p class="mt-2 text-xs text-muted">
                            Baris bermasalah dilewati. Perbaiki di Excel lalu unggah ulang — mahasiswa yang
                            sudah masuk tidak akan dobel karena NIM-nya ditolak.
                        </p>
                    </form>
                @else
                    <x-alert type="warning" :dismissible="false" class="mt-4">
                        Tidak ada baris yang bisa diimpor. Perbaiki keterangan di atas lalu unggah ulang berkasnya.
                    </x-alert>
                @endif
            @endif
        </x-card>
    @endif
</x-admin-layout>
