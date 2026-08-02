<x-admin-layout header="Dashboard Admin">
    <div class="mb-6">
        <p class="text-ink">Selamat datang, <span class="font-medium">{{ auth()->user()->name }}</span>.</p>
        <p class="mt-0.5 text-sm text-muted">Ringkasan data master sistem evaluasi.</p>
    </div>

    {{-- KPI stat cards ala SaleInfo Elegent (§13.3) --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat-card label="Program Studi" :value="$studyProgramCount" icon="study-program" />
        <x-stat-card label="Dosen" :value="$lecturerCount" icon="lecturer" />
        <x-stat-card label="Mahasiswa Aktif" :value="$activeStudentCount" icon="student" />
        <x-stat-card label="Kelas" :value="$classGroupCount" icon="class-group" />
        <x-stat-card label="Mata Kuliah" :value="$courseCount" icon="course" />
        <x-stat-card label="Penugasan Dosen" :value="$assignmentCount" icon="assignment" />
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        {{-- Line chart: evaluasi masuk per hari (ala widget Revenue Elegent, tanpa library) --}}
        <x-card class="lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-semibold text-ink">Evaluasi Masuk</h2>
                <span class="text-xs text-muted">14 hari terakhir</span>
            </div>
            <x-line-chart :points="$dailyEvaluationCounts" :labels="$dailyEvaluationLabels" />
        </x-card>

        {{-- Donut chart: distribusi status mahasiswa (ala widget Website Visitors Elegent) --}}
        <x-card>
            <h2 class="mb-4 font-display text-lg font-semibold text-ink">Status Mahasiswa</h2>
            <x-donut-chart :segments="$studentStatusSegments" />
        </x-card>
    </div>

    {{-- Status periode evaluasi berjalan --}}
    <x-card class="mt-4">
        <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted">Periode Evaluasi Berjalan</h2>
        @if ($openPeriod)
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-display text-lg font-semibold text-ink">{{ $openPeriod->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        <span class="font-mono">{{ $openPeriod->academic_year }}</span> ·
                        {{ $openPeriod->start_date?->translatedFormat('d M Y') }} – {{ $openPeriod->end_date?->translatedFormat('d M Y') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="font-display text-2xl font-semibold text-ink">{{ $openPeriodEvaluationCount }}</p>
                    <p class="text-xs text-muted">evaluasi masuk</p>
                </div>
            </div>
        @else
            <p class="text-sm text-muted">Tidak ada periode evaluasi yang sedang dibuka.
                <a href="{{ route('admin.evaluation-periods.index') }}" class="text-accent hover:underline">Kelola periode →</a>
            </p>
        @endif
    </x-card>

    {{-- Progres per kelas: hanya relevan selama ada periode berjalan, karena
         gunanya untuk menagih kelas yang tertinggal sebelum periode ditutup. --}}
    @if ($openPeriod)
        <x-card class="mt-4">
            <div class="mb-4">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Progres Pengisian per Kelas</h2>
                <p class="mt-1 text-sm text-muted">
                    Diurutkan dari yang terendah. <span class="font-medium text-ink">Selesai</span> dihitung dari
                    mahasiswa yang sudah mengisi seluruh mata kuliahnya.
                </p>
            </div>

            @if (empty($classProgress))
                <x-empty-state message="Belum ada penugasan dosen pada periode ini, jadi belum ada yang bisa dievaluasi mahasiswa." />
            @else
                <x-table>
                    <x-slot name="head">
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Selesai</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Evaluasi</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Progres</th>
                    </x-slot>

                    @foreach ($classProgress as $row)
                        @php
                            $tone = match (true) {
                                $row['percentage'] >= 80 => 'bg-success',
                                $row['percentage'] >= 40 => 'bg-warning',
                                default => 'bg-danger',
                            };
                        @endphp
                        <tr class="hover:bg-accent-soft">
                            <td class="px-4 py-3 font-mono text-ink">{{ $row['class_code'] }}</td>
                            <td class="px-4 py-3 font-mono text-muted">{{ $row['study_program'] }}</td>
                            <td class="px-4 py-3 text-muted">
                                <span class="text-ink">{{ $row['completed'] }}</span> / {{ $row['students'] }} mhs
                            </td>
                            <td class="px-4 py-3 text-muted">
                                <span class="text-ink">{{ $row['submitted'] }}</span> / {{ $row['expected'] }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 shrink-0 overflow-hidden rounded-full bg-border">
                                        <div class="h-full rounded-full {{ $tone }} transition-[width] duration-300 ease-out-quart"
                                            style="width: {{ $row['percentage'] }}%"></div>
                                    </div>
                                    <span class="font-mono text-xs text-muted">{{ rtrim(rtrim(number_format($row['percentage'], 1, ',', ''), '0'), ',') }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </x-card>
    @endif
</x-admin-layout>
