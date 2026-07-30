{{--
    Landing page publik SIEDU.

    Konsep visual mengikuti GUIDELINE §1: "evaluasi sebagai pembacaan instrumen
    (spec-sheet), bukan rating konsumen". Karena itu hero-nya bukan ilustrasi
    dekoratif, melainkan potongan instrumen yang benar-benar bisa dipakai —
    pengunjung mencoba gauge ⬥ yang sama persis dengan yang dipakai mahasiswa,
    dan melihat pembacaan skornya bergerak.

    Token warna/tipografi seluruhnya dari @theme app.css. Accent teal tetap
    dipakai hanya untuk elemen interaktif (§2.3); amber hanya untuk rating.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'SIEDU') }} — Sistem Evaluasi Dosen Terpadu</title>
    <meta name="description"
        content="SIEDU adalah sistem evaluasi dosen untuk Jurusan Teknologi Informasi. Mahasiswa menilai secara anonim, dosen dan kaprodi membaca hasilnya sebagai data yang terukur.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="landing-page font-body text-ink antialiased">
    <a href="#konten"
        class="sr-only rounded-input bg-accent px-4 py-2 text-white focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50">
        Lewati ke konten
    </a>

    {{-- Header: latar surface muncul begitu halaman digulir, supaya hero terasa lapang di atas. --}}
    <header x-data="{ scrolled: false, menu: false }"
        @scroll.window="scrolled = window.scrollY > 8"
        :class="scrolled || menu ? 'bg-surface/95 shadow-md backdrop-blur' : 'bg-transparent'"
        class="fixed inset-x-0 top-0 z-40 transition duration-200 ease-out-quart">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="#" class="flex items-center gap-2">
                <span class="font-display text-lg font-semibold text-ink">SIEDU</span>
                <span class="hidden rounded-full bg-accent-soft px-2 py-0.5 text-xs font-medium text-accent sm:inline">
                    Jurusan Teknologi Informasi
                </span>
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach (['#cara-kerja' => 'Cara Kerja', '#peran' => 'Peran', '#anonimitas' => 'Anonimitas'] as $href => $label)
                    <a href="{{ $href }}"
                        class="rounded-input px-3 py-2 text-sm text-muted transition duration-150 ease-out-quart hover:bg-accent-soft hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                        {{ $label }}
                    </a>
                @endforeach
                <x-button :href="route('login')" class="ml-2">Masuk</x-button>
            </nav>

            <div class="flex items-center gap-2 md:hidden">
                <x-button :href="route('login')" class="px-4 py-2">Masuk</x-button>
                <button type="button" @click="menu = !menu"
                    class="rounded-input p-2 text-muted transition duration-150 ease-out-quart hover:bg-accent-soft hover:text-accent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                    :aria-expanded="menu" aria-controls="menu-mobile" aria-label="Buka menu">
                    <x-icon name="menu" class="size-6" x-show="! menu" />
                    <x-icon name="close" class="size-6" x-show="menu" x-cloak />
                </button>
            </div>
        </div>

        <div id="menu-mobile" x-show="menu" x-cloak @click="menu = false"
            x-transition:enter="transition ease-out-quart duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-out-quart duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="border-t border-border bg-surface px-4 py-2 md:hidden">
            @foreach (['#cara-kerja' => 'Cara Kerja', '#peran' => 'Peran', '#anonimitas' => 'Anonimitas'] as $href => $label)
                <a href="{{ $href }}" class="block rounded-input px-3 py-2.5 text-sm text-ink transition hover:bg-accent-soft">{{ $label }}</a>
            @endforeach
        </div>
    </header>

    <main id="konten">
        {{-- ============================ HERO ============================ --}}
        <section class="relative pt-28 pb-16 sm:pt-36 sm:pb-24">
            <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div class="reveal">
                    <p class="font-mono text-xs uppercase tracking-widest text-accent">Sistem Evaluasi Dosen Terpadu</p>

                    <h1 class="mt-4 font-display text-4xl font-semibold leading-tight text-ink sm:text-5xl lg:text-[3.25rem]">
                        Evaluasi dosen yang dibaca seperti
                        <span class="relative whitespace-nowrap">
                            <span class="relative z-10">instrumen</span>
                            <span class="absolute inset-x-0 bottom-1 z-0 h-3 bg-rating/25" aria-hidden="true"></span>
                        </span>,
                        bukan bintang.
                    </h1>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-muted sm:text-lg">
                        Mahasiswa menilai tiap dosen secara anonim di akhir semester. Hasilnya diagregasi
                        menjadi skor per kategori yang bisa ditindaklanjuti dosen dan kaprodi — terukur,
                        bukan sekadar rata-rata bintang.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <x-button :href="route('login')" class="w-full sm:w-auto">Masuk ke SIEDU</x-button>
                        <x-button variant="secondary" href="#cara-kerja" class="w-full sm:w-auto">Lihat cara kerjanya</x-button>
                    </div>

                    <p class="mt-4 text-xs text-muted">
                        Akun dibuat oleh admin jurusan — tidak ada pendaftaran mandiri.
                    </p>
                </div>

                {{-- Instrumen hidup: gauge yang sama dengan form evaluasi mahasiswa. --}}
                <div class="reveal" style="transition-delay: 120ms"
                    x-data="{
                        skor: { penguasaan: 0, interaksi: 0, kedisiplinan: 0 },
                        get terisi() { return Object.values(this.skor).filter((n) => n > 0).length },
                        get rerata() {
                            const nilai = Object.values(this.skor).filter((n) => n > 0);
                            return nilai.length ? nilai.reduce((a, b) => a + b, 0) / nilai.length : 0;
                        },
                    }">
                    <div class="rounded-card bg-surface p-5 shadow-xl sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Contoh Kuesioner</p>
                                <p class="mt-0.5 text-sm text-ink">Coba geser penilaiannya</p>
                            </div>
                            <span class="rounded-full bg-accent-soft px-2.5 py-1 font-mono text-xs text-accent">MI2B</span>
                        </div>

                        <div class="mt-5 space-y-4">
                            @foreach ([
                                'penguasaan' => 'Dosen menguasai materi yang diajarkan',
                                'interaksi' => 'Dosen terbuka untuk pertanyaan mahasiswa',
                                'kedisiplinan' => 'Dosen hadir dan memulai kelas tepat waktu',
                            ] as $key => $pertanyaan)
                                <div class="border-t border-border pt-4 first:border-0 first:pt-0">
                                    <p class="mb-2 text-sm text-ink">{{ $pertanyaan }}</p>
                                    <div class="flex items-center gap-1" role="radiogroup" aria-label="{{ $pertanyaan }}">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button" role="radio"
                                                :aria-checked="skor.{{ $key }} === {{ $i }}"
                                                aria-label="Nilai {{ $i }}"
                                                @click="skor.{{ $key }} = {{ $i }}"
                                                class="flex size-11 items-center justify-center rounded-input transition duration-150 ease-out-quart hover:bg-canvas focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent">
                                                <span class="text-2xl leading-none transition-colors duration-150"
                                                    :class="skor.{{ $key }} >= {{ $i }} ? 'text-rating' : 'text-border'">⬥</span>
                                            </button>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Pembacaan skor: inti metafora "instrumen". --}}
                        <div class="mt-5 rounded-input bg-canvas p-4">
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-muted">Skor Rata-rata</p>
                                    <p class="mt-1 font-display text-4xl font-semibold leading-none text-ink"
                                        x-text="rerata ? rerata.toFixed(1) : '—'"></p>
                                </div>
                                <p class="font-mono text-xs text-muted">
                                    <span x-text="terisi"></span>/3 pertanyaan
                                </p>
                            </div>

                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-border">
                                <div class="h-full rounded-full bg-rating transition-[width] duration-300 ease-out-quart"
                                    :style="`width: ${(rerata / 5) * 100}%`"></div>
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 text-center text-xs text-muted">
                        Pratinjau — jawaban di sini tidak disimpan.
                    </p>
                </div>
            </div>
        </section>

        {{-- ======================== ANGKA RINGKAS ======================== --}}
        <section class="px-4 sm:px-6 lg:px-8">
            <div class="reveal mx-auto max-w-6xl overflow-hidden rounded-card bg-surface shadow-lg">
                <div class="grid grid-cols-2 gap-px bg-border sm:grid-cols-4">
                    @foreach ([
                        ['4', 'peran pengguna', 'Mahasiswa, dosen, kaprodi, admin'],
                        ['5', 'kategori penilaian', 'Dari penguasaan materi sampai feedback'],
                        ['1–5', 'skala tiap butir', 'Gauge ⬥, bukan bintang'],
                        [(string) config('evaluation.anonymity_min_respondents'), 'minimum responden', 'Sebelum kesan & saran ditampilkan'],
                    ] as [$angka, $label, $keterangan])
                        <div class="bg-surface px-4 py-6 sm:px-6 sm:py-8">
                            <p class="font-display text-3xl font-semibold text-ink sm:text-4xl">{{ $angka }}</p>
                            <p class="mt-1 text-sm font-medium text-ink">{{ $label }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $keterangan }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ========================= CARA KERJA ========================= --}}
        <section id="cara-kerja" class="scroll-mt-20 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="reveal max-w-2xl">
                    <p class="font-mono text-xs uppercase tracking-widest text-accent">Cara Kerja</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-ink sm:text-4xl">
                        Tiga langkah, satu periode evaluasi
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-muted">
                        Hanya ada satu periode evaluasi yang terbuka pada satu waktu. Begitu admin menutupnya,
                        hasilnya langsung terbaca oleh dosen dan kaprodi.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:mt-12 lg:grid-cols-3">
                    @foreach ([
                        ['01', 'evaluate', 'Mahasiswa menilai', 'Sistem menampilkan tepat mata kuliah yang diambil kelasnya pada periode berjalan. Satu form per pasangan mata kuliah–dosen, jadi dosen team teaching dinilai terpisah.'],
                        ['02', 'results', 'Sistem mengagregasi', 'Jawaban dirata-rata per kategori penilaian, bukan ditumpuk jadi satu angka. Kesan & saran dipisahkan dari identitas pengirimnya.'],
                        ['03', 'lecturer', 'Dosen menindaklanjuti', 'Dosen melihat skor per kategori beserta kesan & saran anonim. Kaprodi melihat perbandingan antar dosen dalam prodinya.'],
                    ] as $i => [$nomor, $ikon, $judul, $isi])
                        <div class="reveal group relative rounded-card bg-surface p-6 shadow-lg transition duration-200 ease-out-quart hover:-translate-y-1 hover:shadow-xl"
                            style="transition-delay: {{ $i * 100 }}ms">
                            <div class="flex items-center justify-between">
                                <span class="flex size-11 items-center justify-center rounded-input bg-accent-soft text-accent">
                                    <x-icon :name="$ikon" class="size-5" />
                                </span>
                                <span class="font-mono text-sm text-border transition-colors duration-200 group-hover:text-accent">{{ $nomor }}</span>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-ink">{{ $judul }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-muted">{{ $isi }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- =========================== PERAN =========================== --}}
        <section id="peran" class="scroll-mt-20 py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8"
                x-data="{ aktif: 'mahasiswa' }">
                <div class="reveal max-w-2xl">
                    <p class="font-mono text-xs uppercase tracking-widest text-accent">Peran</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-ink sm:text-4xl">
                        Satu sistem, empat sudut pandang
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-muted">
                        Tiap peran hanya melihat apa yang memang jadi urusannya. Pilih salah satu untuk melihat isinya.
                    </p>
                </div>

                @php
                    $peran = [
                        'mahasiswa' => ['student', 'Mahasiswa', 'Mengisi evaluasi', [
                            'Daftar mata kuliah yang wajib dievaluasi periode ini',
                            'Form kuesioner ⬥ plus kolom kesan & saran',
                            'Tidak bisa mengisi dua kali untuk dosen yang sama',
                        ]],
                        'dosen' => ['lecturer', 'Dosen', 'Membaca hasil sendiri', [
                            'Skor rata-rata per kategori penilaian',
                            'Kesan & saran anonim dari mahasiswa',
                            'Riwayat lintas periode untuk tiap kelas yang diampu',
                        ]],
                        'kaprodi' => ['results', 'Kaprodi', 'Membandingkan antar dosen', [
                            'Agregasi seluruh dosen dalam satu prodi',
                            'Filter per dosen dan per periode evaluasi',
                            'Akses dibatasi ketat pada prodinya sendiri',
                        ]],
                        'admin' => ['users', 'Admin', 'Mengelola data induk', [
                            'CRUD prodi, kelas, mata kuliah, dosen, dan mahasiswa',
                            'Membuka & menutup periode evaluasi',
                            'Promosi semester dengan pratinjau sebelum dijalankan',
                        ]],
                    ];
                @endphp

                <div class="reveal mt-10 flex flex-wrap gap-2" role="tablist" aria-label="Peran pengguna">
                    @foreach ($peran as $kunci => [$ikon, $nama, $ringkas, $poin])
                        <button type="button" role="tab" id="tab-{{ $kunci }}"
                            :aria-selected="aktif === '{{ $kunci }}'"
                            :tabindex="aktif === '{{ $kunci }}' ? 0 : -1"
                            aria-controls="panel-{{ $kunci }}"
                            @click="aktif = '{{ $kunci }}'"
                            :class="aktif === '{{ $kunci }}' ? 'bg-accent text-white shadow-md' : 'bg-surface text-ink shadow-sm hover:bg-accent-soft'"
                            class="flex items-center gap-2 rounded-input px-4 py-2.5 text-sm font-medium transition duration-150 ease-out-quart focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2">
                            <x-icon :name="$ikon" class="size-4" />
                            {{ $nama }}
                        </button>
                    @endforeach
                </div>

                @foreach ($peran as $kunci => [$ikon, $nama, $ringkas, $poin])
                    <div x-show="aktif === '{{ $kunci }}'" x-cloak
                        x-transition:enter="transition ease-out-quart duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        id="panel-{{ $kunci }}" role="tabpanel" aria-labelledby="tab-{{ $kunci }}"
                        class="mt-6 rounded-card bg-surface p-6 shadow-lg sm:p-8">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-input bg-accent-soft text-accent">
                                <x-icon :name="$ikon" class="size-6" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="font-display text-xl font-semibold text-ink">{{ $nama }}</h3>
                                <p class="mt-0.5 text-sm text-muted">{{ $ringkas }}</p>
                            </div>
                        </div>

                        <ul class="mt-6 grid gap-3 sm:grid-cols-3">
                            @foreach ($poin as $butir)
                                <li class="flex items-start gap-2.5 rounded-input bg-canvas p-4 text-sm text-ink">
                                    <x-icon name="check-circle" class="mt-0.5 size-4 shrink-0 text-success" />
                                    <span class="leading-relaxed">{{ $butir }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ========================= ANONIMITAS ========================= --}}
        <section id="anonimitas" class="scroll-mt-20 py-16 sm:py-24">
            <div class="mx-auto grid max-w-6xl items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:gap-16 lg:px-8">
                <div class="reveal">
                    <p class="font-mono text-xs uppercase tracking-widest text-accent">Anonimitas</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold text-ink sm:text-4xl">
                        Jujur menilai tanpa perlu khawatir
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-muted">
                        Evaluasi hanya berguna kalau mahasiswa merasa aman untuk jujur. Karena itu
                        anonimitas dijaga di tingkat sistem, bukan sekadar dijanjikan.
                    </p>

                    <dl class="mt-8 space-y-5">
                        @foreach ([
                            ['Identitas tidak pernah ikut terbaca', 'Dosen dan kaprodi menerima skor beserta teks kesan & saran — tanpa nama, NIM, maupun penanda apa pun yang bisa merujuk ke satu mahasiswa.'],
                            ['Ambang minimum responden', 'Kesan & saran baru ditampilkan setelah minimal '.config('evaluation.anonymity_min_respondents').' mahasiswa mengisi untuk kelas tersebut, supaya tidak bisa ditebak siapa penulisnya.'],
                            ['Terkunci pada periode berjalan', 'Hanya satu periode evaluasi yang terbuka, dan tiap mahasiswa hanya bisa mengisi sekali per dosen — hasilnya tidak bisa dipengaruhi pengisian berulang.'],
                        ] as $i => [$judul, $isi])
                            <div class="flex gap-4">
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-success/15 text-success">
                                    <x-icon name="check-circle" class="size-5" />
                                </span>
                                <div>
                                    <dt class="text-base font-semibold text-ink">{{ $judul }}</dt>
                                    <dd class="mt-1 text-sm leading-relaxed text-muted">{{ $isi }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Ilustrasi: seperti inilah kesan & saran tampil di sisi dosen. --}}
                <div class="reveal" style="transition-delay: 120ms">
                    <div class="rounded-card bg-surface p-5 shadow-xl sm:p-6">
                        <div class="mb-4 flex items-center justify-between gap-2">
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wide text-muted">Kesan &amp; Saran</h3>
                                <p class="mt-0.5 text-xs text-muted">Tampilan di sisi dosen</p>
                            </div>
                            <span class="rounded-full bg-success/15 px-2.5 py-1 text-xs font-medium text-success">
                                {{ config('evaluation.anonymity_min_respondents') }}+ responden
                            </span>
                        </div>

                        <div class="space-y-3">
                            @foreach ([
                                [5, 'Penjelasan materinya runtut dan contohnya nyambung dengan praktikum.'],
                                [4, 'Kelasnya enak diikuti. Kalau slide-nya dibagikan sebelum kelas, akan lebih mudah menyiapkan diri.'],
                            ] as [$nilai, $teks])
                                <div class="rounded-input bg-canvas p-4">
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <span class="flex items-center gap-0.5" aria-label="Nilai {{ $nilai }} dari 5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span class="text-base leading-none {{ $i <= $nilai ? 'text-rating' : 'text-border' }}" aria-hidden="true">⬥</span>
                                            @endfor
                                            <span class="ml-1.5 font-mono text-xs text-muted">{{ number_format($nilai, 1) }}</span>
                                        </span>
                                        <span class="rounded-full bg-muted/15 px-2 py-0.5 text-xs text-muted">Anonim</span>
                                    </div>
                                    <p class="text-sm leading-relaxed text-ink">{{ $teks }}</p>
                                </div>
                            @endforeach
                        </div>

                        <p class="mt-4 border-t border-border pt-4 text-xs leading-relaxed text-muted">
                            Tidak ada nama, NIM, atau waktu pengisian yang ditampilkan — bahkan urutannya tidak
                            mencerminkan urutan pengiriman.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        {{-- ============================ CTA ============================ --}}
        <section class="px-4 pb-16 sm:px-6 sm:pb-24 lg:px-8">
            <div class="reveal relative mx-auto max-w-6xl overflow-hidden rounded-card bg-ink px-6 py-12 text-center shadow-xl sm:px-12 sm:py-16">
                <div class="landing-grid-dark pointer-events-none absolute inset-0" aria-hidden="true"></div>

                <div class="relative">
                    <h2 class="font-display text-3xl font-semibold text-white sm:text-4xl">
                        Siap mengisi evaluasi semester ini?
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-white/70">
                        Masuk dengan akun yang diberikan admin jurusan. Pada login pertama kamu akan
                        diminta mengganti password terlebih dahulu.
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="{{ route('login') }}"
                            class="inline-flex w-full items-center justify-center rounded-input bg-accent px-6 py-3 text-sm font-medium text-white transition duration-150 ease-out-quart hover:brightness-110 hover:shadow-md active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink sm:w-auto">
                            Masuk ke SIEDU
                        </a>
                        <a href="#cara-kerja"
                            class="inline-flex w-full items-center justify-center rounded-input border border-white/25 px-6 py-3 text-sm font-medium text-white transition duration-150 ease-out-quart hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-ink sm:w-auto">
                            Pelajari lagi
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-border bg-surface">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-center sm:flex-row sm:px-6 sm:text-left lg:px-8">
            <div>
                <p class="font-display text-base font-semibold text-ink">SIEDU</p>
                <p class="mt-0.5 text-sm text-muted">Sistem Evaluasi Dosen Terpadu — Jurusan Teknologi Informasi</p>
            </div>
            <p class="font-mono text-xs text-muted">&copy; {{ now()->year }}</p>
        </div>
    </footer>
</body>

</html>
