<x-admin-layout header="Promosi Semester">
    @if ($blockingPeriod)
        <x-alert type="warning" :dismissible="false" class="mb-4 max-w-2xl">
            Periode evaluasi <span class="font-medium">{{ $blockingPeriod->name }}</span> masih dibuka.
            Tutup dulu periode tersebut sebelum menaikkan semester — mahasiswa yang pindah kelas akan
            kehilangan akses ke evaluasi yang belum sempat diisi.
        </x-alert>
    @endif

    <x-card class="max-w-2xl">
        <p class="text-sm text-muted">
            Menaikkan mahasiswa aktif <span class="font-medium text-ink">satu semester</span> dari periode yang dipilih.
            Dari <span class="font-medium text-ink">ganjil</span> mahasiswa naik ke semester genap dan tetap di kelas yang sama;
            dari <span class="font-medium text-ink">genap</span> mahasiswa naik kelas satu tingkat di tahun ajaran berikutnya.
            Mahasiswa yang menuntaskan semester terakhir prodinya otomatis berstatus <span class="font-medium text-ink">Lulus</span>.
            Mahasiswa cuti &amp; DO tidak ikut. Aman dijalankan berulang.
        </p>

        {{-- Pratinjau: GET & read-only, jadi aman di-refresh berkali-kali. --}}
        <form method="GET" action="{{ route('admin.semester-promotion.preview') }}" class="mt-6 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="academic_year" :value="'Tahun Ajaran Periode Asal'" />
                    <x-select id="academic_year" name="academic_year" class="mt-1" required>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year }}" @selected(request('academic_year') === $year)>{{ $year }}</option>
                        @endforeach
                    </x-select>
                    <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="semester_type" :value="'Semester Periode Asal'" />
                    <x-select id="semester_type" name="semester_type" class="mt-1" required>
                        <option value="ganjil" @selected(request('semester_type') === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected(request('semester_type') === 'genap')>Genap</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('semester_type')" class="mt-1" />
                </div>
            </div>

            <x-button type="submit" variant="secondary">Pratinjau Perubahan</x-button>
        </form>
    </x-card>

    @if ($preview)
        <x-card class="mt-4 max-w-2xl">
            <div class="mb-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Pratinjau</h2>
                <p class="mt-1 text-sm text-ink">
                    Periode tujuan:
                    <span class="font-medium">{{ ucfirst($preview['to_semester_type']->value) }}</span>
                    <span class="font-mono">{{ $preview['to_academic_year'] }}</span>
                </p>
                <p class="mt-0.5 text-sm text-muted">
                    {{ $preview['students_promoted'] }} mahasiswa naik semester &middot;
                    {{ $preview['students_graduated'] }} lulus
                </p>
            </div>

            @if (empty($preview['rows']))
                <x-empty-state message="Tidak ada mahasiswa yang cocok untuk periode ini. Pastikan tahun ajaran & tipe semesternya benar." />
            @else
                <x-table>
                    <x-slot name="head">
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Semester</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Mahasiswa</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Hasil</th>
                    </x-slot>

                    @foreach ($preview['rows'] as $row)
                        <tr class="hover:bg-accent-soft">
                            <td class="px-4 py-3 font-mono text-ink">
                                {{ $row['from_class'] }}
                                @unless ($row['graduates'] || $row['to_class'] === $row['from_class'])
                                    <span class="text-muted">→ {{ $row['to_class'] }}</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3 text-muted">
                                {{ $row['from_semester'] }}
                                @unless ($row['graduates'])
                                    → <span class="text-ink">{{ $row['to_semester'] }}</span>
                                @endunless
                            </td>
                            <td class="px-4 py-3 text-ink">{{ $row['students'] }}</td>
                            <td class="px-4 py-3">
                                @if ($row['graduates'])
                                    <span class="rounded-full bg-muted/15 px-2 py-0.5 text-xs text-muted">Lulus</span>
                                @elseif ($row['to_class'] === $row['from_class'])
                                    <span class="rounded-full bg-accent-soft px-2 py-0.5 text-xs text-accent">Naik semester</span>
                                @else
                                    <span class="rounded-full bg-success/15 px-2 py-0.5 text-xs text-success">Naik kelas</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-table>

                <form method="POST" action="{{ route('admin.semester-promotion.run') }}" class="mt-4"
                    onsubmit="return confirm('Jalankan promosi semester sesuai pratinjau ini?')">
                    @csrf
                    <input type="hidden" name="academic_year" value="{{ request('academic_year') }}">
                    <input type="hidden" name="semester_type" value="{{ request('semester_type') }}">
                    <x-button type="submit" :variant="$blockingPeriod ? 'disabled' : 'primary'">Jalankan Promosi</x-button>
                </form>
            @endif
        </x-card>
    @endif
</x-admin-layout>
