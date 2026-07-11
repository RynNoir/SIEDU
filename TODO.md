# TODO — SIEDU (Sistem Evaluasi Dosen Terpadu)

Langkah pembangunan project berdasarkan [PRD.md](PRD.md) (fungsional) dan [GUIDELINE.md](GUIDELINE.md) (design system). Dikelompokkan per fase. Centang `[x]` saat selesai. Urutan sudah memperhatikan dependensi antar tahap.

**Aturan implementasi UI**: setiap task yang menyentuh tampilan (Fase 0, 5, 7, 8, 9, 10) **wajib** mengikuti token warna/tipografi/komponen di GUIDELINE.md — jangan pakai palet/komponen Tailwind default begitu saja. Referensi bagian GUIDELINE.md dicantumkan inline di tiap task terkait.

**Status project saat ini**: Fase 0 & Fase 1 selesai (2026-07-05). Fase 0: Laravel 13 + MySQL (`siedu`), Laravel Breeze (Blade stack) terpasang, Tailwind v4 + design token GUIDELINE.md aktif, font Space Grotesk/IBM Plex ter-import, `config/evaluation.php` dibuat. Fase 1: seluruh 12 migration tabel domain PRD sudah dibuat (1 commit per tabel), `php artisan migrate` sukses, FK & unique constraint terverifikasi via `information_schema`. Siap lanjut ke Fase 2 (Models & Relationships).

**Ringkasan keputusan kunci dari PRD** (baca sebelum mulai):
- 4 role: `admin`, `lecturer`, `student`, **`kaprodi`** (role terpisah — keputusan project, lihat bawah) — semua akun dibuat admin, tidak ada self-registration.
- `must_change_password` memaksa ganti password saat login pertama.
- Kurikulum paket → **tidak ada tabel enrollment/KRS**. Kelas = kohort naik tingkat bersama.
- **Team teaching (v1.1)**: satu MK di satu kelas + periode boleh diampu >1 dosen. Unique constraint `course_class_assignments` = (`course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`). Evaluasi dilakukan **per baris assignment** (per pasangan dosen–MK), bukan per MK.
- Skala kuesioner: 1–5 seragam untuk semua pertanyaan. Kolom database tetap `star_rating` (PRD), tapi **UI ditampilkan sebagai Rating Gauge (notch/diamond ⬥)**, bukan bintang — lihat GUIDELINE.md §5.
- Anonimitas: `student_id` tidak boleh pernah bocor ke endpoint dosen.
- Threshold anonimitas: kesan & saran hanya tampil ke dosen bila responden ≥ 5 (configurable).

## Keputusan Project (Terkonfirmasi)

| # | Keputusan | Nilai |
|---|---|---|
| 1 | Database | **MySQL/MariaDB** (bukan SQLite default skeleton) |
| 2 | Starter auth | **Laravel Breeze (Blade stack)**, dikustomisasi untuk 4 role |
| 3 | Kaprodi | **Role terpisah** (`role=kaprodi` di tabel `users`), bukan sekadar view admin |
| 4 | Periode evaluasi | **Harus tunggal** — hanya boleh ada 1 `evaluation_period` berstatus `open` di satu waktu (enforce di service/controller saat membuka periode) |
| 5 | Password default akun baru | `"password"` (hashed via bcrypt saat seed/create) |
| 6 | Nama aplikasi | **SIEDU — Sistem Evaluasi Dosen Terpadu** |

---

## Fase 0 — Persiapan Lingkungan

- [x] Konfigurasi `.env`: set `DB_CONNECTION=mysql`, `DB_DATABASE=siedu` (atau nama sesuai konvensi lokal), kredensial DB MySQL/MariaDB. Update `config/database.php` default connection bila perlu.
- [x] Jalankan `php artisan key:generate` bila belum.
- [x] Buat database MySQL kosong, jalankan `php artisan migrate` untuk verifikasi koneksi.
- [x] Install **Laravel Breeze (Blade stack)**: `composer require laravel/breeze --dev` → `php artisan breeze:install blade --no-interaction` → `npm install && npm run build`. **Catatan**: `breeze:install blade` men-downgrade Tailwind ke v3 (`tailwind.config.js`/`postcss.config.js` + `@tailwind` directives) — dikembalikan manual ke v4 (`@tailwindcss/vite`, `@import 'tailwindcss'` di `app.css`, hapus config JS v3).
- [x] Update branding: nama aplikasi **SIEDU** di `.env` (`APP_NAME="SIEDU"`), layout Breeze (`resources/views/layouts`), title halaman login.
- [x] Instal Tailwind (sudah v4 di project, terbawa dari Breeze scaffold) — pastikan `npm run dev` jalan.
- [x] **Setup design token (GUIDELINE.md §2, §3, §7, §11)**: tambahkan blok `@theme` di `resources/css/app.css` (Tailwind v4, CSS-based config, bukan `tailwind.config.js`) berisi semua CSS custom property dari GUIDELINE.md §11 (`--color-ink`, `--color-canvas`, `--color-surface`, `--color-border`, `--color-accent`, `--color-accent-soft`, `--color-rating`, `--color-success`, `--color-warning`, `--color-danger`, `--color-muted`, radius, spacing).
- [x] **Import font (GUIDELINE.md §3.1)**: tambahkan Google Fonts *Space Grotesk*, *IBM Plex Sans*, *IBM Plex Mono* via `<link>` di layout utama (atau self-host via npm bila lebih disukai), map ke `--font-display`, `--font-body`, `--font-mono`.
- [x] Buat `config/evaluation.php` untuk nilai configurable: `anonymity_min_respondents` (default 5), `default_password` (default `"password"`).

---

## Fase 1 — Database: Migration 12 Tabel

Buat via `php artisan make:migration --no-interaction`. Perhatikan **urutan** karena FK. Gunakan `foreignId()->constrained()`.

- [x] `study_programs`: `name`, `code` (string 10), `degree_level` enum('D3','D4'), `total_semesters` tinyint. *(Migration paling awal — tidak ada FK.)*
- [x] Extend/ubah migration `users`: tambah `role` enum('admin','lecturer','student','kaprodi'), `must_change_password` boolean default true, `study_program_id` **nullable** FK → `study_programs` (dipakai khusus saat `role=kaprodi`, lihat Fase 9). *(Edit migration users bawaan, bukan bikin baru.)*
- [x] `class_groups`: FK `study_program_id`, `academic_year` (string 9), `year_level` tinyint, `class_letter` (string 1), `class_code` (string 10), `capacity` int default 25. **Unique** (`academic_year`, `class_code`).
- [x] `courses`: FK `study_program_id`, `name`, `code` (string 20), `semester` tinyint, `credit_hours` tinyint.
- [x] `lecturers`: FK `user_id`, `name`, `nip` unique, FK `study_program_id` (homebase).
- [x] `students`: FK `user_id`, `nim` unique, `name`, FK `study_program_id`, FK `class_group_id`, `current_semester` tinyint, `status` enum('aktif','cuti','DO','lulus') default 'aktif', FK `created_by` → users.
- [x] `evaluation_periods`: `name`, `academic_year` (string 9), `semester_type` enum('ganjil','genap'), `start_date` date, `end_date` date, `status` enum('draft','open','closed') default 'draft'.
- [x] `evaluation_questions`: `category` string, `question_text` text, `order_number` int, `is_active` boolean default true.
- [x] `course_class_assignments`: FK `course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`, `created_by` → users. **Unique** (`course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`) — v1.1 team teaching.
- [x] `evaluations`: FK `student_id`, `course_class_assignment_id`, `evaluation_period_id`, `submitted_at` timestamp. **Unique** (`student_id`, `course_class_assignment_id`, `evaluation_period_id`).
- [x] `evaluation_answers`: FK `evaluation_id`, `evaluation_question_id`, `star_rating` tinyint.
- [x] `evaluation_impressions`: FK `evaluation_id` **unique**, `impression_text` text nullable, `suggestion_text` text nullable.
- [x] Tambah index pada kolom FK yang sering difilter (`evaluations.course_class_assignment_id`, dsb.) — otomatis ter-index via `foreignId()->constrained()` (indexing standar, PRD §8).
- [x] `php artisan migrate` sukses tanpa error; diverifikasi manual via `information_schema` (MCP `database-schema` tidak tersedia di sesi ini) — 20 FK dan seluruh unique constraint (termasuk 4 kolom team teaching & 3 kolom anti-submit-ganda) terkonfirmasi benar.

---

## Fase 2 — Models & Relationships

Buat via `php artisan make:model --no-interaction`. Untuk tiap model: `$fillable`/`$guarded`, `casts`, relasi Eloquent, dan factory.

- [x] `StudyProgram` — hasMany: classGroups, courses, lecturers, students.
- [x] `User` (edit existing) — cast `must_change_password` bool; hasOne lecturer/student; belongsTo studyProgram (nullable, dipakai kaprodi); helper `isAdmin()`, `isLecturer()`, `isStudent()`, `isKaprodi()`.
- [x] `ClassGroup` — belongsTo studyProgram; hasMany students, courseClassAssignments. Accessor untuk `class_code` bila mau auto-generate.
- [x] `Course` — belongsTo studyProgram; hasMany courseClassAssignments.
- [x] `Lecturer` — belongsTo user, studyProgram; hasMany courseClassAssignments.
- [x] `Student` — belongsTo user, studyProgram, classGroup, creator (created_by); hasMany evaluations. Cast `status`.
- [x] `EvaluationPeriod` — hasMany courseClassAssignments, evaluations. Cast dates + status. Scope `open()`. Method/observer untuk menegakkan **hanya 1 periode `open`** dalam satu waktu (tolak/`closed`-kan periode lain saat satu dibuka).
- [x] `EvaluationQuestion` — scope `active()`, order by `order_number`.
- [x] `CourseClassAssignment` — belongsTo course, lecturer, classGroup, evaluationPeriod, creator; hasMany evaluations.
- [x] `Evaluation` — belongsTo student, courseClassAssignment, evaluationPeriod; hasMany answers; hasOne impression.
- [x] `EvaluationAnswer` — belongsTo evaluation, question.
- [x] `EvaluationImpression` — belongsTo evaluation.
- [x] (Opsional) Enum PHP untuk `role`, `status` mahasiswa, `status` periode, `semester_type`, `degree_level` (TitleCase keys, sesuai konvensi PHP project).
- [x] Arch/unit test ringan: pastikan relasi tidak error (`Model::factory()->create()`).

---

## Fase 3 — Seeder & Factory Data Dummy

Buat factory untuk tiap model, lalu seeder terstruktur. Panggil dari `DatabaseSeeder`.

- [x] `StudyProgramSeeder`: 5 prodi persis PRD §2.1 (MI, TK, SI D3/6 sem; TRPL, ANIM D4/8 sem).
- [x] `EvaluationQuestionSeeder`: pertanyaan template PRD §5.3 (5 kategori, semua format "Bagaimana penilaian Anda terhadap...", + 1 rangkuman keseluruhan), dengan `order_number` berurutan.
- [x] `AdminSeeder`: 1 akun admin (`role=admin`, `must_change_password=false` agar bisa langsung login untuk setup).
- [x] `KaprodiSeeder`: 1 akun kaprodi per prodi (`role=kaprodi`, `study_program_id` diisi sesuai kolom yang ditambahkan di Fase 1), password default `"password"`, `must_change_password=true`.
- [x] `ClassGroupSeeder`: generate beberapa kelas dummy per prodi untuk ≥1 tahun ajaran (mis. `MI1A/B`, `TK1A`, `TRPL1A`, dst). Terapkan aturan `class_code` = `{KODE}{TAHUN}{HURUF}` dan `tahun = ceil(semester/2)`.
- [x] `CourseSeeder`: kurikulum paket dummy per prodi per semester (beberapa MK per semester dengan SKS).
- [x] `StudentSeeder` + factory: ~25 mahasiswa/kelas, `status=aktif`, `current_semester` konsisten dgn `year_level`. Buat `user` terkait (role=student). NIM unik.
- [x] `LecturerSeeder` + factory: beberapa dosen per prodi, `user` role=lecturer, NIP unik.
- [x] `EvaluationPeriodSeeder`: 1 periode `open` (aktif sekarang) + 1 periode `closed` (histori untuk uji filter perbandingan).
- [x] `CourseClassAssignmentSeeder`: assign dosen ke MK+kelas untuk periode aktif. **Sertakan ≥1 kasus team teaching** (2 dosen untuk 1 MK di 1 kelas) untuk menguji jalur v1.1.
- [x] (Opsional untuk uji dashboard dosen) `EvaluationSeeder`: generate evaluasi + jawaban + kesan/saran dummy dari sebagian mahasiswa, cukup untuk menembus threshold ≥5 responden di beberapa assignment.
- [x] Jalankan `php artisan migrate:fresh --seed`, verifikasi jumlah baris via `database-query`.

---

## Fase 4 — Autentikasi, Role & Force-Change-Password

- [x] Breeze Blade sudah terpasang (Fase 0). Login pakai email + password.
- [x] Middleware `role` (mis. `EnsureUserHasRole`) — daftarkan alias di `bootstrap/app.php` (Laravel 13). Batasi grup route per role (`admin`, `lecturer`, `student`, `kaprodi`).
- [x] Middleware `MustChangePassword`: jika `must_change_password=true`, redirect paksa ke halaman ganti password (kecuali route logout & route ganti password itu sendiri).
- [x] Halaman & controller ganti password: set password baru + `must_change_password=false`.
- [x] Redirect pasca-login sesuai role: admin→dashboard admin, lecturer→dashboard dosen, student→daftar evaluasi, kaprodi→dashboard prodi.
- [x] Feature test Pest: login tiap role, redirect force-password, middleware menolak akses lintas-role.

---

## Fase 5 — Modul Admin (Master Data CRUD)

Grup route `admin` + middleware role. Controller resourceful, FormRequest untuk validasi, Blade views. **Ikuti GUIDELINE.md §4.4 (wireframe tabel), §6.1 (tombol), §6.2 (form), §6.3 (tabel), §6.4 (badge status), §6.7 (empty state)** — bangun komponen Blade reusable (`<x-table>`, `<x-badge-status>`, `<x-button>`, dsb.) sekali di fase ini agar dipakai ulang di Fase 7–9.

- [x] Komponen Blade dasar sesuai GUIDELINE.md: `<x-button variant="primary|secondary|destructive|disabled">` (§6.1), `<x-badge-status>` untuk status aktif/cuti/DO/periode (§6.4, pill + label teks, jangan hanya warna), `<x-table>` dengan header sticky `color-canvas` + border 1px tanpa zebra-stripe (§6.3), kolom NIM/kode kelas/kode MK pakai kelas `font-mono` `text-mono-data` (§3.2).
- [x] CRUD `study_programs`.
- [x] CRUD `class_groups` (auto-generate `class_code` dari prodi+year+letter; validasi unik per academic_year).
- [x] CRUD `courses` (validasi: `semester` 7/8 hanya untuk prodi D4 — PRD §7.2).
- [x] CRUD akun `lecturers` (buat `user` role=lecturer + `must_change_password=true` + password default sekaligus).
- [x] CRUD akun `students` (buat `user` role=student; set `created_by`; validasi konsistensi `current_semester` ↔ `year_level` kelas — PRD §7.1). Tabel data mahasiswa mengikuti wireframe GUIDELINE.md §4.4 (search NIM/nama + filter chip prodi/kelas/status).
- [x] CRUD `evaluation_periods` + aksi buka/tutup (`draft`→`open`→`closed`). **Tegakkan periode tunggal**: saat admin membuka satu periode, sistem otomatis mengubah periode `open` lain (jika ada) menjadi `closed` sebelum periode baru dibuka — dilakukan dalam 1 transaction di controller/service. Badge status pakai `<x-badge-status>` (GUIDELINE.md §6.4: Open = teal, Closed = abu).
- [x] CRUD `evaluation_questions` (kelola kategori string, urutan, aktif/nonaktif).
- [x] CRUD `course_class_assignments`: form pilih course+lecturer+class+period; set `created_by`. **Dukung tambah >1 dosen (team teaching)**; tegakkan unique 4-kolom & tolak duplikat dgn pesan jelas (pesan error mengikuti nada lugas GUIDELINE.md §12, misal "Dosen ini sudah diassign ke mata kuliah & kelas yang sama pada periode ini").
- [x] Validasi §7.1 & §7.2 diterapkan di FormRequest assignment (semester course cocok year_level kelas).
- [x] Empty state untuk tabel kosong (belum ada data) mengikuti prinsip GUIDELINE.md §6.7: jelas, informatif, jelaskan kondisi — bukan sekadar "Data tidak ditemukan".
- [x] Feature test CRUD utama + aturan validasi jenjang/semester.

---

## Fase 6 — ClassPromotionService (Promosi Kelas Tahunan)

Implementasi PRD §6.1. Service class + command Artisan + (opsional) tombol admin.

- [x] `app/Services/ClassPromotionService.php` via `make:class`. Method `promote(string $fromAcademicYear, string $toAcademicYear)`.
- [x] Logika: ambil `class_groups` tahun berjalan → skip yang sudah `year_level` maksimum prodi (lulus) → buat `class_groups` baru (`year_level+1`, `class_letter` sama, `class_code` regen) → pindahkan mahasiswa `status=aktif` ke kelas baru + `current_semester += 2`.
- [x] Kecualikan `cuti` (assignment manual nanti) dan `DO` (tetap di kelas terakhir untuk histori).
- [x] Bungkus dalam DB transaction; idempotent/aman bila dijalankan dua kali (cek kelas tujuan sudah ada).
- [x] Command `php artisan class:promote {fromYear} {toYear}` memanggil service.
- [x] Unit/feature test skenario: mahasiswa aktif naik, cuti tetap, DO tetap, kelas tahun akhir tidak dinaikkan (lulus).

---

## Fase 7 — Modul Mahasiswa (Pengisian Evaluasi)

Grup route `student`. Inti anti-submit-ganda & jalur team teaching. **Ikuti wireframe GUIDELINE.md §4.2 (Form Evaluasi Mahasiswa) dan elemen signature §5 (Rating Gauge).**

- [x] Halaman daftar evaluasi: query `course_class_assignments` dengan `class_group_id` = kelas mahasiswa saat ini pada periode `open`. Tandai mana yang **sudah** vs **belum** diisi (join ke `evaluations`).
- [x] **Team teaching**: tiap baris assignment = satu kartu evaluasi terpisah. Label jelas "Nama MK — Nama Dosen" (PRD §6.3.4).
- [x] Form evaluasi per assignment: semua `evaluation_questions` aktif (rating gauge 1–5, wajib semua) + 2 textarea kesan & saran (nullable), label "Kesan"/"Saran" terpisah (GUIDELINE.md §6.5).
- [x] **Komponen `<x-rating-gauge>` (GUIDELINE.md §5)**: 5 notch/diamond (⬥), BUKAN bintang (★) — kosong = `color-border`, terisi = `color-rating` (amber), transisi hover/pilih 150–200ms (§8 Motion). Mode interaktif untuk form ini; tampilkan skor numerik `text-mono-data` di samping (misal "4 / 5"). Wajib bisa diakses via keyboard (Tab + Enter/Space, GUIDELINE.md §9) dan target sentuh ≥44×44px di mobile.
- [x] Submit handler: buat `evaluation` + `evaluation_answers` + `evaluation_impression` dalam 1 transaction; set `submitted_at`. Tolak jika sudah pernah submit (unique constraint + cek eksplisit).
- [x] Guard: mahasiswa hanya bisa mengisi assignment untuk kelasnya sendiri & periode `open`; tolak akses assignment lain.
- [x] Tombol submit berlabel aksi konkret **"Kirim Evaluasi"** (GUIDELINE.md §6.1, §12) — bukan "Submit"/"OK"; pesan error validasi eksplisit misal "Semua pertanyaan wajib diberi nilai sebelum mengirim" (§12).
- [x] Responsif mobile (GUIDELINE.md §10): form single-column penuh, gauge rating diperbesar untuk mudah ditekan jempol, sidebar berubah jadi bottom nav sederhana khusus role mahasiswa.
- [x] Feature test: submit sukses, cegah submit ganda, team teaching muncul sebagai 2 form, akses lintas-kelas ditolak.

---

## Fase 8 — Modul Dosen (Dashboard Hasil + Kesan & Saran Anonim)

Grup route `lecturer`. **Kritis: anonimitas — `student_id` tidak boleh muncul di query/response manapun.** **Ikuti wireframe GUIDELINE.md §4.3 (Dashboard Dosen).**

- [x] Dashboard: daftar MK/kelas yang diampu dosen login (dari `course_class_assignments` di mana `lecturer_id` = dosen ini).
- [x] Skor rata-rata **per kategori pertanyaan** (agregasi `evaluation_answers.star_rating` di-group per `evaluation_questions.category`), plus rata-rata keseluruhan & jumlah responden — tampilkan sebagai kartu ringkasan (`text-display-l` untuk angka besar) sesuai wireframe §4.3.
- [x] Skor per kategori ditampilkan sebagai **bar horizontal proporsional (Rating Gauge mode display-only, GUIDELINE.md §5)** di bawah label kategori, bukan gauge interaktif — beri kesan "meter", bukan "rating toko online".
- [x] Daftar kesan & saran anonim per assignment sebagai **kartu (GUIDELINE.md §6.5)**: border 1px + radius 8px, badge kecil **"Anonim"** di pojok kartu, rating gauge kecil berdampingan, teks Kesan/Saran sebagai 2 blok terpisah berlabel (bukan satu paragraf gabungan). **Jangan** select/expose `student_id`.
- [x] Filter per kelas/periode/rentang rating ditampilkan sebagai **chip dropdown horizontal** di atas konten (GUIDELINE.md §6.6), bukan sidebar filter terpisah.
- [x] **Threshold anonimitas**: kesan & saran hanya tampil bila jumlah `evaluations` pada assignment ≥ `config('evaluation.anonymity_min_respondents')` (default 5). Bila belum, tampilkan empty state persis nada GUIDELINE.md §6.7: *"Kesan & saran akan tampil setelah minimal 5 mahasiswa mengisi evaluasi untuk kelas ini."*
- [x] Larang filter granular yang bisa mengidentifikasi individu (PRD §7.6) — hanya sediakan filter yang diizinkan.
- [x] Authorization: dosen hanya melihat data assignment miliknya (Policy/gate).
- [x] Copy nada netral-informatif untuk data sensitif (GUIDELINE.md §12): misal "Kesan & saran ditampilkan tanpa identitas mahasiswa" — bukan nada defensif/berlebihan.
- [x] Feature test: agregasi skor benar, kesan tersembunyi di bawah threshold, `student_id` tidak ada di response, dosen lain tak bisa lihat data bukan miliknya.

---

## Fase 9 — Modul Kaprodi (Role Terpisah)

PRD §6.5 awalnya menyebut kaprodi opsional; **project ini menetapkan kaprodi sebagai role wajib/terpisah** (perluasan dari PRD 12-tabel asli — lihat catatan di bawah). Kolom `users.study_program_id` sudah dibuat di Fase 1 dan model di-set di Fase 2; fase ini fokus ke UI/dashboard-nya.

- [ ] Middleware role: `kaprodi` sudah masuk daftar role tervalidasi (Fase 4) — pastikan grup route `kaprodi` dipisah dari `admin`.
- [ ] Dashboard agregasi level prodi: filter per dosen, per periode — query dibatasi ke `study_program_id` milik kaprodi login (`auth()->user()->study_program_id`). **Reuse komponen visual dashboard dosen dari Fase 8** (kartu ringkasan, rating gauge display-only, filter chip GUIDELINE.md §6.6) supaya konsisten secara visual, tinggal ganti sumber data jadi agregat lintas-dosen.
- [ ] Perbandingan skor antar dosen yang mengampu MK sama di kelas paralel (dalam prodi yang sama) — tabel ringkas mengikuti GUIDELINE.md §6.3 (tanpa zebra-stripe, kolom kode kelas pakai `text-mono-data`).
- [ ] Tetap patuhi anonimitas & threshold (kesan & saran ≥5 responden, tanpa `student_id`); empty state & copy sensitif mengikuti GUIDELINE.md §6.7 dan §12 (sama seperti Fase 8).
- [ ] Authorization: kaprodi tidak bisa mengakses data prodi lain (Policy/gate).
- [ ] Feature test: kaprodi hanya lihat data prodinya, threshold & anonimitas tetap berlaku.

---

## Fase 10 — Polish, Validasi Menyeluruh & Testing E2E

- [ ] Konsistensi UI: audit seluruh halaman terhadap GUIDELINE.md — token warna (§2) dipakai sesuai peran (accent hanya elemen interaktif, amber hanya rating, status color hanya badge), tipografi (§3) konsisten (Space Grotesk judul, IBM Plex Sans body, IBM Plex Mono untuk NIM/kode kelas/kode MK di semua tempat termasuk dropdown/chip).
- [ ] Layout & sidebar per role (§4.1, §10): sidebar tetap desktop ≥1024px, collapse-icon tablet 768–1023px, bottom-nav mobile <768px khusus mahasiswa.
- [ ] Motion (§8): transisi hover/fokus/dropdown 150–200ms ease-out, tanpa animasi dekoratif; hormati `prefers-reduced-motion`.
- [ ] Aksesibilitas (§9): kontras WCAG AA, semua elemen interaktif (termasuk rating gauge) bisa diakses keyboard, target sentuh ≥44×44px mobile, badge status selalu sertakan label teks bukan warna saja.
- [ ] Copy/microcopy (§12): audit label tombol pakai kata kerja aktif & konsisten dengan notifikasi hasil (misal "Simpan Evaluasi" → "Evaluasi Tersimpan"), istilah yang dikenal pengguna bukan istilah teknis sistem.
- [ ] Validasi form lengkap + pesan error ramah (bahasa Indonesia, langsung ke inti masalah — GUIDELINE.md §6.2, §12).
- [ ] Review keamanan: cek ulang tidak ada kebocoran `student_id`; middleware role menutup semua route; mass-assignment aman.
- [ ] Jalankan `vendor/bin/pint --format agent` untuk seluruh PHP.
- [ ] Suite Pest E2E: alur penuh admin→assign→mahasiswa isi→dosen lihat hasil; jalur team teaching; promosi kelas.
- [ ] `php artisan test --compact` hijau.
- [ ] (Opsional) Browser/smoke test halaman utama tiap role (Pest 4 browser) untuk cek error JS.
- [ ] `npm run build` untuk aset produksi.

---

## Catatan Perluasan dari PRD Asli

Dua keputusan project ini **menambah/mengubah** skema 12-tabel asli PRD — dicatat di sini agar tidak terlewat:
1. **Kolom `role` di `users`** bertambah 1 nilai enum: `'kaprodi'` (PRD asli hanya admin/lecturer/student).
2. **Kolom baru `users.study_program_id`** (nullable FK, ditambahkan di Fase 1) untuk membatasi cakupan dashboard kaprodi ke prodi yang dipimpinnya (dipakai di Fase 9). Tidak menambah tabel baru — tetap 12 tabel.
3. **Penegakan periode tunggal**: PRD tidak eksplisit melarang >1 periode `open` bersamaan — project ini menegakkan **hanya 1 periode `open`** di satu waktu (lihat Fase 5 & Fase 2 `EvaluationPeriod`).
