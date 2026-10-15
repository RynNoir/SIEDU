# SIEDU — Sistem Evaluasi Dosen Terpadu

Aplikasi web untuk mengelola evaluasi dosen oleh mahasiswa di **Jurusan Teknologi Informasi, Politeknik Negeri Padang**. SIEDU menggantikan pengisian evaluasi lewat formulir manual/Google Form dengan sistem terstruktur yang menjaga anonimitas mahasiswa sekaligus memberi dosen dan kaprodi hasil yang bisa dianalisis per kategori.

Setiap evaluasi terdiri dari dua bagian:

1. **Kuesioner terstruktur** — pertanyaan berskala bintang 1–5 yang seragam, dikelompokkan per kategori (penguasaan materi, interaksi, kedisiplinan, penilaian & feedback, rangkuman keseluruhan).
2. **Kesan & Saran** — teks bebas yang ditampilkan ke dosen secara **anonim**.

---

## Daftar Isi

- [Fitur](#fitur)
- [Peran Pengguna](#peran-pengguna)
- [Konsep Akademik](#konsep-akademik)
- [Anonimitas & Keamanan](#anonimitas--keamanan)
- [Tech Stack](#tech-stack)
- [Instalasi](#instalasi)
- [Akun Demo](#akun-demo)
- [Perintah Penting](#perintah-penting)
- [Konfigurasi](#konfigurasi)
- [Struktur Project](#struktur-project)
- [Testing](#testing)
- [Dokumentasi Lanjutan](#dokumentasi-lanjutan)

---

## Fitur

### Admin
- **Master data**: program studi, kelas, mata kuliah (kurikulum paket), pertanyaan kuesioner, akun dosen & mahasiswa.
- **Penugasan dosen** ke kombinasi mata kuliah + kelas + periode, termasuk **team teaching** (satu mata kuliah diampu lebih dari satu dosen).
- **Periode evaluasi** berstatus `draft` → `open` → `closed`. Hanya satu periode yang boleh dibuka; membuka periode baru otomatis menutup periode sebelumnya.
- **Impor massal mahasiswa via CSV** dengan alur unduh template → unggah → pratinjau per baris (lengkap dengan alasan penolakan) → konfirmasi.
- **Promosi semester** per periode dengan pratinjau (dry-run) sebelum dieksekusi. Diblokir selama masih ada periode evaluasi yang dibuka.
- **Halaman Alumni** terpisah untuk mahasiswa yang sudah lulus.
- **Pemantauan progres pengisian** per kelas di dashboard, diurutkan dari yang terendah, supaya admin tahu kelas mana yang perlu diingatkan.

### Mahasiswa
- Melihat daftar evaluasi yang wajib diisi berdasarkan kelas dan periode aktif.
- Mengisi kuesioner bintang + kesan & saran untuk setiap pasangan mata kuliah–dosen. Mata kuliah team teaching muncul sebagai form terpisah per dosen.
- Submit ganda dicegah di tingkat database.

### Dosen
- Dashboard hasil evaluasi untuk mata kuliah yang diampu: skor rata-rata per kategori, tingkat partisipasi, dan kesan & saran anonim.
- Filter per kelas, per periode, dan per rentang rating.
- **Ekspor CSV**: rekap seluruh kelas yang diampu atau detail satu penugasan.

### Kaprodi
- Dashboard agregasi tingkat program studi, otomatis terkunci ke prodi milik akunnya.
- Membandingkan skor antar dosen di kelas paralel (adil karena kurikulumnya identik).
- Ekspor CSV rekap prodi untuk laporan ke jurusan.

---

## Peran Pengguna

| Role | Akses | Dibuat oleh |
|---|---|---|
| `admin` | Seluruh master data, penugasan, periode, promosi semester | Seeder |
| `lecturer` | Hasil evaluasi mata kuliah yang diampu | Admin |
| `student` | Mengisi evaluasi pada periode aktif | Admin |
| `kaprodi` | Agregasi hasil evaluasi satu program studi | Admin |

Tidak ada pendaftaran mandiri. Semua akun dibuat admin dengan password default dan **wajib diganti saat login pertama**.

---

## Konsep Akademik

**Program studi** yang didukung:

| Kode | Program Studi | Jenjang | Semester |
|---|---|---|---|
| MI | Manajemen Informatika | D3 | 6 |
| TK | Teknik Komputer | D3 | 6 |
| SI | Sistem Informasi | D3 | 6 |
| TRPL | Teknologi Rekayasa Perangkat Lunak | D4 | 8 |
| ANIM | Animasi | D4 | 8 |

- **Penamaan kelas**: `{KODE_PRODI}{TAHUN_KE}{HURUF}`, misalnya `MI1A` atau `TRPL3B`, dengan `tahun = ceil(semester / 2)`.
- **Kelas sebagai kohort**: mahasiswa naik tingkat bersama (`MI1B` → `MI2B` → `MI3B`). Kohort diidentifikasi oleh pasangan angkatan (`enrollment_year`) + huruf kelas.
- **Kurikulum paket**: mata kuliah ditetapkan per prodi per semester, jadi tidak ada tabel KRS/enrollment. Yang berubah tiap periode hanya dosen pengampunya.
- **Promosi semester**: dijalankan sekali tiap akhir periode dan menaikkan mahasiswa aktif satu semester. Pada pergantian genap → ganjil, mahasiswa juga pindah ke kelas tingkat berikutnya. Mahasiswa yang menuntaskan semester terakhir otomatis berstatus `lulus`. Prosesnya idempoten karena memfilter berdasarkan paritas semester tiap mahasiswa.
- **Status mahasiswa**: `aktif`, `cuti`, `DO`, `lulus`. Hanya mahasiswa aktif yang ikut promosi dan dihitung dalam progres pengisian.

---

## Anonimitas & Keamanan

- `student_id` **tidak pernah** diekspos ke halaman maupun file unduhan dosen dan kaprodi.
- Kesan & saran baru ditampilkan setelah jumlah responden per penugasan mencapai **ambang minimum** (default 5, dapat dikonfigurasi). Aturan ini juga berlaku pada file ekspor CSV.
- Tidak tersedia filter granular yang bisa dipakai untuk mengidentifikasi individu.
- Otorisasi berbasis role lewat middleware `role:*` dan policy untuk akses penugasan.
- Middleware `EnsurePasswordChanged` memaksa pengguna mengganti password default.
- Kolom `created_by` pada mahasiswa dan penugasan dosen sebagai audit trail.

---

## Tech Stack

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.3+ (dikembangkan di 8.5), Laravel 13 |
| Database | MySQL / MariaDB |
| Autentikasi | Laravel Breeze (Blade), dikustomisasi untuk 4 role |
| Frontend | Blade, Tailwind CSS v4, Alpine.js, htmx |
| Build tool | Vite |
| Testing | Pest v4 |
| Code style | Laravel Pint |

Grafik dan meter skor dibuat dengan SVG/CSS tanpa library chart tambahan. Ekspor dan impor memakai CSV (BOM UTF-8, pemisah titik koma, desimal koma) agar langsung terbaca Excel berlokal Indonesia tanpa dependency tambahan.

---

## Instalasi

### Prasyarat
- PHP 8.3 atau lebih baru beserta ekstensi standar Laravel
- Composer
- Node.js & npm
- MySQL atau MariaDB

### Langkah

```bash
# 1. Clone repository
git clone https://github.com/RynNoir/SIEDU.git
cd SIEDU

# 2. Install dependency & siapkan .env
composer install
cp .env.example .env
php artisan key:generate

# 3. Buat database bernama "siedu", lalu sesuaikan DB_* di .env

# 4. Migrasi + data contoh
php artisan migrate --seed

# 5. Build aset frontend
npm install
npm run build
```

Jalankan server pengembangan (web server, queue worker, dan Vite sekaligus):

```bash
composer run dev
```

Aplikasi dapat diakses di `http://localhost:8000`.

> Alternatif cepat: `composer run setup` menjalankan install dependency, membuat `.env`, generate key, migrasi, dan build aset dalam satu perintah (tanpa seeder).

---

## Akun Demo

Setelah `php artisan migrate --seed`, tersedia data contoh: 5 prodi, kelas, kurikulum, 4 dosen per prodi, 25 mahasiswa per kelas, pertanyaan kuesioner, satu periode historis (closed), satu periode aktif (open), penugasan dosen, dan contoh evaluasi.

| Role | Email | Password |
|---|---|---|
| Admin | `admin@siedu.test` | `password` |
| Kaprodi | `kaprodi.mi@siedu.test`, `kaprodi.tk@siedu.test`, `kaprodi.si@siedu.test`, `kaprodi.trpl@siedu.test`, `kaprodi.anim@siedu.test` | `password` |
| Dosen & Mahasiswa | Dibuat acak oleh seeder; lihat emailnya di menu **Dosen** / **Mahasiswa** pada panel admin | `password` |

Akun kaprodi akan diminta mengganti password pada login pertama.

---

## Perintah Penting

```bash
# Promosi semester lewat CLI (padanan halaman Promosi Semester di panel admin)
php artisan semester:promote 2025/2026 ganjil
php artisan semester:promote 2025/2026 genap

# Tembus blokir periode yang masih dibuka (untuk keperluan pemulihan)
php artisan semester:promote 2025/2026 genap --force

# Format kode
vendor/bin/pint
```

---

## Konfigurasi

Selain variabel standar Laravel, SIEDU memakai pengaturan di [config/evaluation.php](config/evaluation.php):

| Variabel `.env` | Default | Keterangan |
|---|---|---|
| `EVALUATION_ANONYMITY_MIN_RESPONDENTS` | `5` | Minimum responden sebelum kesan & saran ditampilkan ke dosen/kaprodi |
| `EVALUATION_DEFAULT_PASSWORD` | `password` | Password default akun yang dibuat admin |

---

## Struktur Project

```
app/
├── Console/Commands/      # semester:promote
├── Enums/                 # Role, StudentStatus, PeriodStatus, SemesterType, DegreeLevel
├── Http/
│   ├── Controllers/
│   │   ├── Admin/         # Master data, penugasan, periode, impor, promosi, alumni
│   │   ├── Lecturer/      # Dashboard & ekspor dosen
│   │   ├── Kaprodi/       # Dashboard & ekspor kaprodi
│   │   └── Student/       # Pengisian evaluasi
│   ├── Middleware/        # EnsureUserHasRole, EnsurePasswordChanged
│   └── Requests/          # Form request validation
├── Models/                # 12 model Eloquent
├── Policies/              # Otorisasi akses penugasan
└── Services/              # Logika bisnis
    ├── AssignmentResultService.php     # Skor per kategori + ambang anonimitas
    ├── EvaluationProgressService.php   # Progres pengisian per kelas
    ├── EvaluationExportService.php     # Ekspor rekap & detail
    ├── SemesterPromotionService.php    # Promosi semester & kelulusan
    ├── StudentImportService.php        # Impor massal mahasiswa
    ├── CsvExportService.php
    └── CsvImportService.php
resources/views/
├── admin/  lecturer/  kaprodi/  student/   # Halaman per role
└── components/                             # Komponen Blade (layout, tabel, chart, badge, dll.)
```

### Skema Database

12 tabel inti: `study_programs`, `class_groups`, `courses`, `lecturers`, `students`, `users`, `evaluation_periods`, `evaluation_questions`, `course_class_assignments`, `evaluations`, `evaluation_answers`, `evaluation_impressions`.

Constraint penting:
- `course_class_assignments` unik pada (`course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`), sehingga team teaching tetap bisa dilakukan.
- `evaluations` unik pada (`student_id`, `course_class_assignment_id`, `evaluation_period_id`) untuk mencegah submit ganda.

Validasi bisnis tambahan: semester mata kuliah harus cocok dengan tingkat kelas, semester 7–8 hanya untuk prodi D4, dan paritas semester mata kuliah (ganjil/genap) harus sesuai dengan tipe periode evaluasi.

---

## Testing

```bash
php artisan test
```

Test suite (Pest) mencakup autentikasi, akses per role, CRUD admin, penugasan dosen, pengisian evaluasi, ambang anonimitas, ekspor dan impor CSV, promosi semester, progres pengisian, integritas penghapusan data, keamanan, serta alur end-to-end.

---

## Dokumentasi Lanjutan

- [PRD.md](PRD.md) — kebutuhan produk, skema database, business logic, dan riwayat revisi
- [GUIDELINE.md](GUIDELINE.md) — panduan desain antarmuka (warna, tipografi, komponen)
- [TODO.md](TODO.md) — rencana dan status pengembangan per fase

---

## Lisensi

Dirilis di bawah lisensi [MIT](https://opensource.org/licenses/MIT).
