# PRD — SIEDU: Sistem Evaluasi Dosen Terpadu (Kuesioner + Kesan & Saran)
## Politeknik Negeri Padang — Jurusan Teknologi Informasi

---

## 1. Ringkasan Project

Platform berbasis Laravel untuk mengelola evaluasi dosen oleh mahasiswa di lingkungan Jurusan Teknologi Informasi, mencakup 5 program studi. Sistem terdiri dari dua komponen penilaian:

1. **Kuesioner terstruktur** — pertanyaan dengan skala bintang 1-5 yang seragam untuk semua item.
2. **Kesan & Saran** — feedback teks bebas yang ditampilkan ke dosen secara **anonim**, dengan kemampuan filter (per kelas, per prodi, per periode, per rentang rating).

Seluruh akun pengguna (mahasiswa & dosen) dibuat oleh **admin** — tidak ada self-registration. Mata kuliah bersifat **paket/tetap** per program studi per semester (bukan pilihan bebas seperti KRS), sehingga tidak ada tabel enrollment. Yang bersifat dinamis tiap semester hanyalah **penugasan dosen pengampu** ke kombinasi mata kuliah dan kelas — dan satu mata kuliah **boleh diampu lebih dari satu dosen sekaligus** (team teaching), yang berarti mahasiswa akan mengisi evaluasi terpisah untuk masing-masing dosen pengampu mata kuliah tersebut.

### Tujuan
- Menggantikan pencatatan evaluasi dosen manual/Google Form dengan sistem terstruktur.
- Memberi kaprodi/dosen insight per kategori pertanyaan, bukan hanya skor tunggal.
- Menjaga anonimitas mahasiswa sambil tetap memungkinkan analisis terfilter.

### Di Luar Scope (Tidak Dikerjakan)
- Analisis sentimen otomatis / NLP terhadap teks kesan & saran (fitur ini **dihilangkan** dan diganti kesan & saran manual yang dibaca langsung oleh dosen).
- Self-registration akun oleh mahasiswa/dosen.
- Sistem KRS/pemilihan mata kuliah bebas.
- Multi-jurusan (scope hanya 1 jurusan: Teknologi Informasi).

---

## 2. Struktur Akademik

### 2.1 Jurusan & Program Studi

Jurusan: **Teknologi Informasi** (tidak perlu tabel `departments` terpisah — hardcode sebagai konteks aplikasi, karena scope tetap 1 jurusan).

| Kode | Nama Prodi | Jenjang | Total Semester |
|---|---|---|---|
| MI | Manajemen Informatika | D3 | 6 |
| TK | Teknik Komputer | D3 | 6 |
| SI | Sistem Informasi | D3 | 6 |
| TRPL | Teknologi Rekayasa Perangkat Lunak | D4 | 8 |
| ANIM | Animasi | D4 | 8 |

### 2.2 Aturan Penamaan Kelas

Format: **`{KODE_PRODI}{TAHUN_KE}{HURUF}`**

- Contoh: `MI1A`, `MI1B`, `MI1C`, `TRPL3B`, `ANIM4A`
- Konversi semester → tahun: **`tahun = ceil(semester / 2)`**
  - Semester 1–2 → tahun 1
  - Semester 3–4 → tahun 2
  - Semester 5–6 → tahun 3
  - Semester 7–8 → tahun 4 (khusus D4: TRPL, ANIM)
- `class_letter` (A/B/C/dst) ditentukan berdasarkan jumlah kelas paralel per angkatan.
- Rata-rata kapasitas per kelas: **25 mahasiswa** (bukan hard limit ketat, hanya estimasi).

### 2.3 Konsep Kohort Kelas (PENTING)

Kelas adalah **kohort yang naik tingkat bersama** dari tahun ke tahun — bukan kelompok yang dibentuk ulang tiap tahun ajaran.

- Mahasiswa yang masuk di `MI1B` akan otomatis menjadi `MI2B` di tahun ajaran berikutnya, `MI3B` di tahun setelahnya, dst.
- Anggota kelas **tidak berubah** kecuali ada mahasiswa DO atau cuti.
- Setiap tahun ajaran baru, dijalankan **proses "promosi kelas"** (lihat bagian 6.1) yang membuat `class_groups` baru dengan `year_level + 1` dan `class_letter` yang sama, lalu memindahkan mahasiswa aktif ke kelas baru tersebut.

**Penanganan kasus khusus:**
- **Mahasiswa DO**: status diubah menjadi `DO`, tidak ikut proses promosi, `class_group_id` tetap menunjuk ke kelas terakhir aktif (untuk histori).
- **Mahasiswa cuti**: status diubah menjadi `cuti`, tidak ikut promosi otomatis. Saat aktif kembali, admin melakukan **assignment manual** ke `class_group` dengan `class_letter` sama namun `year_level` yang sesuai dengan semester dia saat itu.

### 2.4 Kurikulum Paket (Fixed Curriculum)

- Mata kuliah sudah ditentukan oleh jurusan per prodi per semester — mahasiswa tidak memilih sendiri.
- Implikasi: **tidak ada tabel KRS/enrollment**. Semua mahasiswa dalam satu kelas otomatis mengikuti paket mata kuliah sesuai semester mereka.
- Yang berubah tiap periode hanyalah **dosen pengampu** untuk kombinasi mata kuliah + kelas tertentu (lihat tabel `course_class_assignments`).

---

## 3. Peran Pengguna (User Roles)

| Role | Deskripsi | Dibuat Oleh |
|---|---|---|
| **admin** | Mengelola seluruh master data: prodi, kelas, mata kuliah, akun dosen & mahasiswa, penugasan dosen, periode evaluasi, pertanyaan kuesioner | Seed awal / superadmin |
| **lecturer (dosen)** | Melihat hasil evaluasi (skor kuesioner + kesan & saran anonim) untuk mata kuliah yang diampu, dengan filter | Admin |
| **student (mahasiswa)** | Mengisi kuesioner evaluasi + kesan & saran untuk setiap mata kuliah yang diampu dosen di kelasnya, pada periode evaluasi aktif | Admin |
| **kaprodi** **[REVISI v1.2]** | Melihat dashboard agregasi hasil evaluasi tingkat program studi (filter per dosen, per periode); cakupan dibatasi otomatis ke satu program studi lewat kolom `study_program_id` pada akunnya | Admin |

**Catatan keamanan**: Setiap akun baru dibuat dengan password default dan flag `must_change_password = true`, memaksa ganti password saat login pertama kali.

---

## 4. Skema Database (12 Tabel)

> Skema ini adalah hasil final setelah proses penyederhanaan dari rancangan awal (16 tabel). Tabel yang dihapus: `departments`, `academic_years`, `question_categories`, `student_class_histories`, `sentiment_results`, `improvement_recommendations`, `department_analytics`.

### 4.1 `study_programs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| name | string | "Manajemen Informatika" |
| code | string(10) | "MI", "TRPL", dst |
| degree_level | enum('D3','D4') | |
| total_semesters | tinyint | 6 untuk D3, 8 untuk D4 |
| timestamps | | |

### 4.2 `class_groups`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| study_program_id | FK → study_programs | |
| academic_year | string(9) | "2025/2026" |
| year_level | tinyint | 1–4 |
| class_letter | string(1) | "A", "B", dst |
| class_code | string(10), unique per academic_year | "MI1A", "TRPL3B" |
| capacity | int, default 25 | |
| timestamps | | |

### 4.3 `courses` (mata kuliah paket)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| study_program_id | FK → study_programs | |
| name | string | |
| code | string(20) | |
| semester | tinyint | 1–8, semester mata kuliah ini ditempuh |
| credit_hours | tinyint | SKS |
| timestamps | | |

### 4.4 `course_class_assignments` (dosen ampu MK di kelas per periode — mendukung multi-dosen/team teaching)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| course_id | FK → courses | |
| lecturer_id | FK → lecturers | |
| class_group_id | FK → class_groups | |
| evaluation_period_id | FK → evaluation_periods | |
| created_by | FK → users (admin) | audit trail |
| timestamps | | |

**Unique constraint**: (`course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`) — mencegah dosen yang sama diassign dua kali ke kombinasi mata kuliah + kelas + periode yang sama.

**[REVISI] Dukungan multi-dosen per mata kuliah**: Satu mata kuliah di satu kelas di satu periode **boleh diampu lebih dari satu dosen** (team teaching). Setiap baris di tabel ini merepresentasikan satu pasangan dosen–mata kuliah–kelas–periode. Contoh: jika mata kuliah "Basis Data" di kelas `MI2A` diampu 2 dosen, maka ada 2 baris di tabel ini dengan `course_id`, `class_group_id`, dan `evaluation_period_id` yang sama, tapi `lecturer_id` berbeda. Implikasinya, **evaluasi dilakukan per baris** (per pasangan dosen-mata kuliah), bukan per mata kuliah — lihat bagian 6.3 untuk detail alur mahasiswa.

### 4.5 `users` (akun login semua role)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| name | string | |
| email | string, unique | |
| password | string, hashed | |
| role | enum('admin','lecturer','student','kaprodi') | **[REVISI v1.2]** ditambah nilai `kaprodi` |
| must_change_password | boolean, default true | |
| study_program_id | FK → study_programs, **nullable** | **[REVISI v1.2]** hanya diisi saat `role='kaprodi'`, membatasi cakupan dashboard ke satu prodi |
| timestamps | | |

### 4.6 `lecturers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| user_id | FK → users | |
| name | string | |
| nip | string, unique | |
| study_program_id | FK → study_programs | prodi homebase dosen |
| timestamps | | |

### 4.7 `students`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| user_id | FK → users | |
| nim | string, unique | |
| name | string | |
| study_program_id | FK → study_programs | |
| class_group_id | FK → class_groups | kelas saat ini |
| current_semester | tinyint | 1–8 |
| status | enum('aktif','cuti','DO','lulus') | |
| created_by | FK → users (admin) | audit trail |
| timestamps | | |

### 4.8 `evaluation_periods`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| name | string | "Ganjil 2025/2026" |
| academic_year | string(9) | |
| semester_type | enum('ganjil','genap') | |
| start_date | date | |
| end_date | date | |
| status | enum('draft','open','closed') | |
| timestamps | | |

### 4.9 `evaluation_questions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| category | string | label kategori, contoh: "Penguasaan Materi" (disimpan sebagai string, bukan tabel relasi terpisah) |
| question_text | text | contoh: "Bagaimana penilaian Anda terhadap penguasaan dosen terhadap materi yang diajarkan?" |
| order_number | int | urutan tampil |
| is_active | boolean, default true | |
| timestamps | | |

### 4.10 `evaluations`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| student_id | FK → students | disimpan untuk validasi, **tidak pernah diekspos ke endpoint dosen** |
| course_class_assignment_id | FK → course_class_assignments | |
| evaluation_period_id | FK → evaluation_periods | |
| submitted_at | timestamp | |
| timestamps | | |

**Unique constraint**: (`student_id`, `course_class_assignment_id`, `evaluation_period_id`) — mencegah submit ganda.

### 4.11 `evaluation_answers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| evaluation_id | FK → evaluations | |
| evaluation_question_id | FK → evaluation_questions | |
| star_rating | tinyint | 1–5 |
| timestamps | | |

### 4.12 `evaluation_impressions`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| evaluation_id | FK → evaluations, unique (1 per evaluasi) | |
| impression_text | text, nullable | kesan mahasiswa |
| suggestion_text | text, nullable | saran mahasiswa |
| timestamps | | |

---

## 5. Desain Kuesioner

### 5.1 Skala Penilaian
- **Skala tunggal**: bintang 1–5, digunakan **seragam untuk semua pertanyaan**.
- Makna skala konsisten: 1 = Sangat Buruk/Sangat Tidak Setuju, 5 = Sangat Baik/Sangat Setuju.

### 5.2 Aturan Redaksi Pertanyaan (WAJIB DIIKUTI)
Semua pertanyaan **harus** dirumuskan sebagai pernyataan kualitas dalam bentuk **"Bagaimana penilaian Anda terhadap [aspek]?"** — bukan pernyataan fakta/frekuensi mentah. Ini kunci agar skala bintang tunggal tetap masuk akal di semua pertanyaan.

❌ Salah: "Dosen hadir tepat waktu"
✅ Benar: "Bagaimana penilaian Anda terhadap kedisiplinan dan ketepatan waktu dosen?"

### 5.3 Contoh Kategori & Pertanyaan Template

**Kategori: Penguasaan & Penyampaian Materi**
- "Bagaimana penilaian Anda terhadap penguasaan dosen terhadap materi yang diajarkan?"
- "Bagaimana penilaian Anda terhadap kejelasan dosen dalam menyampaikan materi?"
- "Bagaimana penilaian Anda terhadap relevansi contoh yang diberikan dosen dalam menjelaskan materi?"

**Kategori: Interaksi & Ketersediaan**
- "Bagaimana penilaian Anda terhadap keterbukaan dosen dalam menerima pertanyaan/diskusi?"
- "Bagaimana penilaian Anda terhadap kemudahan menghubungi dosen di luar jam kelas?"

**Kategori: Kedisiplinan & Profesionalisme**
- "Bagaimana penilaian Anda terhadap kedisiplinan dan ketepatan waktu dosen?"
- "Bagaimana penilaian Anda terhadap kejelasan informasi dosen jika ada perubahan jadwal?"

**Kategori: Penilaian & Feedback**
- "Bagaimana penilaian Anda terhadap kejelasan kriteria penilaian tugas/ujian?"
- "Bagaimana penilaian Anda terhadap kualitas feedback yang diberikan dosen atas tugas Anda?"

**Kategori: Rangkuman Keseluruhan**
- "Secara keseluruhan, bagaimana penilaian Anda terhadap kualitas pengajaran dosen ini?"

### 5.4 Kesan & Saran
Setelah kuesioner bintang, mahasiswa mengisi 2 kolom teks bebas:
- **Kesan**: "Apa yang paling Anda sukai dari cara mengajar dosen ini?"
- **Saran**: "Apa yang menurut Anda perlu diperbaiki?"

---

## 6. Business Logic & Alur Kerja

### 6.1 Proses Promosi Kelas Tahunan (Admin, Sekali per Tahun Ajaran)

Dijalankan sekali di awal tahun ajaran baru:

1. Ambil semua `class_groups` di tahun ajaran berjalan.
2. Untuk tiap kelas, jika `year_level` sudah mencapai batas maksimum prodi (misal D3 sudah di tahun 3), skip — kelas ini lulus, tidak dinaikkan lagi.
3. Jika belum, buat `class_groups` baru dengan:
   - `year_level = year_level_lama + 1`
   - `class_letter` **sama** dengan kelas lama
   - `class_code` digenerate ulang sesuai `year_level` baru
4. Pindahkan semua mahasiswa dengan `status = 'aktif'` di kelas lama ke `class_group_id` baru, dan tambahkan `current_semester + 2`.
5. Mahasiswa dengan `status = 'cuti'` **tidak** ikut dipindah otomatis — tetap di kelas lama sampai admin melakukan assignment manual saat mahasiswa tersebut aktif kembali.
6. Mahasiswa dengan `status = 'DO'` tetap di kelas terakhir untuk keperluan histori, tidak ikut proses apapun.

### 6.2 Alur Kerja Admin (Rutin)

1. **Setup awal** (jarang berubah): input `study_programs`, `courses` (kurikulum paket per prodi per semester).
2. **Tiap tahun ajaran baru**: jalankan proses promosi kelas (6.1), buat `class_groups` untuk mahasiswa baru (tahun 1), buat akun mahasiswa baru.
3. **Tiap periode/semester**: assign dosen ke `course_class_assignments` untuk kombinasi mata kuliah + kelas yang aktif pada periode tersebut — ini yang paling sering berubah. **[REVISI]** Admin dapat menambahkan **lebih dari satu dosen** untuk mata kuliah yang sama di kelas yang sama (team teaching) dengan cukup membuat baris tambahan di `course_class_assignments` dengan `lecturer_id` berbeda.
4. **Kelola periode evaluasi**: buka (`status = 'open'`) dan tutup (`status = 'closed'`) periode evaluasi. **[REVISI v1.2]** Periode evaluasi bersifat **tunggal** — hanya boleh ada satu `evaluation_periods` berstatus `open` pada satu waktu. Saat admin membuka periode baru, sistem otomatis menutup (`status = 'closed'`) periode `open` yang masih berjalan.

### 6.3 Alur Kerja Mahasiswa

1. Login (wajib ganti password jika `must_change_password = true`).
2. Sistem menampilkan daftar evaluasi yang harus diisi: didapat dari `course_class_assignments` yang `class_group_id`-nya sama dengan `class_group_id` mahasiswa saat ini, pada `evaluation_period_id` yang sedang `open`.
3. Mahasiswa mengisi kuesioner bintang untuk semua `evaluation_questions` aktif + kolom kesan & saran, **per baris `course_class_assignment`** (artinya per pasangan mata kuliah + dosen).
4. **[REVISI] Penanganan team teaching**: jika satu mata kuliah diampu lebih dari satu dosen, mata kuliah tersebut akan muncul sebagai **beberapa evaluasi terpisah** di daftar mahasiswa — satu form penuh (kuesioner + kesan & saran) untuk masing-masing dosen, bukan digabung dalam satu form untuk mata kuliah tersebut. Sebaiknya UI menampilkan label yang jelas, misal "Basis Data — Dosen A" dan "Basis Data — Dosen B" agar mahasiswa tidak bingung mengisi dua form untuk mata kuliah yang sama.
5. Sistem mencegah submit ganda (unique constraint pada tabel `evaluations`, berbasis `course_class_assignment_id` sehingga otomatis berlaku benar per dosen).

### 6.4 Alur Kerja Dosen

1. Login, melihat dashboard hasil evaluasi untuk mata kuliah yang diampu.
2. Melihat skor rata-rata per kategori pertanyaan.
3. Melihat kesan & saran anonim, dengan filter:
   - Per kelas (jika mengampu beberapa kelas paralel)
   - Per periode (bandingkan semester ini vs sebelumnya)
   - Per rentang rating (misal hanya tampilkan dari mahasiswa yang beri rating rendah)
4. **`student_id` tidak pernah ditampilkan atau dapat diakses lewat endpoint dosen.**

### 6.5 Alur Kerja Kaprodi **[REVISI v1.2 — Role Wajib, Bukan Opsional]**

Kaprodi login dengan akun `role='kaprodi'`, cakupan datanya otomatis dibatasi ke `study_program_id` yang tersimpan pada akunnya (satu akun kaprodi = satu prodi).

Dashboard agregasi level prodi:
- Filter per dosen, per periode (filter "per prodi" tidak diperlukan lagi karena sudah otomatis terbatas ke prodi sendiri).
- Bandingkan skor antar dosen yang mengampu mata kuliah sama di kelas paralel berbeda (adil karena kurikulum identik/paket).
- Tetap tunduk pada aturan anonimitas & threshold minimum responden (§7.5) dan larangan filter granular (§7.6) — kaprodi **tidak** dapat melihat `student_id` maupun kesan & saran individual di bawah threshold.

---

## 7. Aturan Validasi Penting

1. **Konsistensi semester**: `courses.semester` harus sesuai dengan `year_level` kelas mahasiswa saat assignment dibuat (semester 1-2 untuk year_level 1, dst).
2. **Validasi jenjang**: mata kuliah dengan `semester` 7 atau 8 hanya valid untuk prodi berjenjang D4 (TRPL, ANIM).
3. **[REVISI] Unik penugasan**: satu `course_id` + `lecturer_id` + `class_group_id` + `evaluation_period_id` hanya boleh muncul **sekali** (mencegah dosen yang sama diassign dobel ke kombinasi yang sama). Namun satu `course_id` + `class_group_id` + `evaluation_period_id` **boleh** memiliki beberapa baris dengan `lecturer_id` berbeda — ini yang memungkinkan **team teaching** (lebih dari satu dosen pengampu untuk mata kuliah yang sama di kelas yang sama).
4. **Cegah submit ganda**: satu `student_id` hanya boleh punya satu `evaluations` untuk `course_class_assignment_id` + `evaluation_period_id` yang sama.
5. **Minimum threshold anonimitas**: kesan & saran untuk satu `course_class_assignment_id` hanya ditampilkan ke dosen (dan kaprodi) jika jumlah `evaluations` yang masuk sudah mencapai minimal **5 responden** (nilai bisa dikonfigurasi).
6. **Batas akses filter**: filter kesan & saran tidak boleh dikombinasikan sampai tingkat yang bisa mengidentifikasi individu (misal jangan sediakan filter granular seperti "per mahasiswa tertentu").
7. **[REVISI v1.2] Periode evaluasi tunggal**: hanya boleh ada satu baris `evaluation_periods` berstatus `open` pada satu waktu. Membuka periode baru harus menutup periode `open` sebelumnya (ditegakkan di level aplikasi/service, bukan constraint database).

---

## 8. Non-Functional Requirements

- **Keamanan**: password di-hash (bcrypt/argon2 default Laravel), paksa ganti password default via `must_change_password`. **[REVISI v1.2]** Password default akun baru buatan admin ditetapkan `"password"`.
- **Anonimitas**: `student_id` pada `evaluations` tidak boleh muncul di response API/endpoint yang diakses role `lecturer` maupun `kaprodi`.
- **Audit trail**: kolom `created_by` pada `students` dan `course_class_assignments` untuk melacak admin yang menginput.
- **Autentikasi & otorisasi**: middleware berbasis `role` (admin/lecturer/student/**kaprodi**) untuk membatasi akses route/controller.
- **Skala data**: estimasi 500–800 mahasiswa aktif (5 prodi × beberapa kelas × ~25 mahasiswa), skala kecil-menengah, tidak butuh optimasi database khusus di luar indexing standar (foreign key, unique constraint).

---

## 9. Tech Stack

- **Framework**: Laravel 13 (PHP 8.5)
- **Database**: MySQL/MariaDB
- **Autentikasi**: **[REVISI v1.2]** Laravel Breeze (Blade stack), dikustomisasi untuk 4 role (admin/lecturer/student/kaprodi)
- **Frontend**: Blade + Tailwind CSS v4 — desain minimal dan clean.
- **Testing**: Pest v4.

---

## 10. Prioritas Pengembangan (Saran Urutan untuk Claude Code)

1. Setup project Laravel + migration untuk 12 tabel di atas beserta foreign key & unique constraint.
2. Seeder: 5 `study_programs`, generate `class_groups` dummy (beberapa tahun ajaran), `courses` (kurikulum paket dummy per prodi), `evaluation_questions` template (5 kategori seperti contoh di bagian 5.3).
3. Autentikasi + middleware role (admin/lecturer/student) + fitur `must_change_password`.
4. Modul Admin: CRUD `study_programs`, `class_groups`, `courses`, akun `students` & `lecturers`, `course_class_assignments`, `evaluation_periods`, `evaluation_questions`.
5. Fitur Service `ClassPromotionService` untuk proses naik tingkat kelas tahunan.
6. Modul Mahasiswa: daftar evaluasi yang harus diisi (berdasarkan kelas + periode aktif), form kuesioner bintang + kesan & saran, validasi anti-submit-ganda.
7. Modul Dosen: dashboard hasil evaluasi (skor rata-rata per kategori), daftar kesan & saran anonim dengan filter (kelas/periode/rentang rating) + penerapan minimum threshold responden.
8. Modul Kaprodi: dashboard agregasi antar dosen dalam satu prodi, dibatasi ke `study_program_id` akun kaprodi.
9. Polish UI/UX (clean, minimal), validasi form, testing alur end-to-end.

---

## 11. Glosarium

- **Kohort**: kelompok mahasiswa yang masuk bersamaan dan naik tingkat bersama, direpresentasikan oleh `class_letter` yang konsisten dari tahun ke tahun.
- **Paket kurikulum**: mata kuliah yang sudah ditetapkan jurusan per prodi per semester, tidak dipilih bebas oleh mahasiswa.
- **Periode evaluasi**: rentang waktu (biasanya 1 semester) di mana mahasiswa dapat mengisi evaluasi dosen.
- **Kesan & Saran**: fitur feedback teks bebas anonim, pengganti fitur analisis sentimen otomatis yang sempat direncanakan namun dihilangkan dari scope.
- **Team teaching**: skenario satu mata kuliah diampu lebih dari satu dosen sekaligus di kelas dan periode yang sama.
- **SIEDU**: Sistem Evaluasi Dosen Terpadu — nama resmi aplikasi ini (ditetapkan v1.2).

---

## 12. Riwayat Revisi

| Tanggal/Versi | Perubahan |
|---|---|
| v1.0 | Skema awal 12 tabel, asumsi 1 mata kuliah = 1 dosen pengampu per kelas per periode |
| v1.1 | **[REVISI]** Mendukung multi-dosen (team teaching) per mata kuliah: unique constraint pada `course_class_assignments` diubah dari (`course_id`, `class_group_id`, `evaluation_period_id`) menjadi (`course_id`, `lecturer_id`, `class_group_id`, `evaluation_period_id`). Mahasiswa kini mengisi evaluasi terpisah per dosen jika mata kuliah diampu lebih dari satu dosen. **Tidak ada tabel baru** yang ditambahkan — perubahan hanya pada constraint dan business logic. |
| v1.3 (UI) | **[REVISI TAMPILAN — tidak mengubah requirement fungsional]** Pengayaan app shell & dashboard (Fase 11): sidebar berikon + collapsible, topbar avatar-dropdown, kartu statistik (KPI), bar/meter via SVG-CSS **tanpa menambah library chart**, dan halaman auth split. Semua tetap memakai token & tipografi GUIDELINE.md (teal/navy/canvas/amber, Space Grotesk + IBM Plex) dan mempertahankan filosofi "minimal & clean" (§9). Detail di GUIDELINE.md §13. Skema database, role, dan business logic **tidak berubah**. |
| v1.2 (2026-07-05) | **[REVISI]** Keputusan implementasi dikonfirmasi bersama [TODO.md](TODO.md): (1) Nama aplikasi ditetapkan **SIEDU — Sistem Evaluasi Dosen Terpadu**. (2) Role **kaprodi ditetapkan wajib/terpisah** (bukan opsional) — tabel `users` bertambah nilai enum `role='kaprodi'` dan kolom baru `study_program_id` (nullable FK) untuk membatasi cakupan dashboard ke satu prodi; **tidak menambah tabel baru**, tetap 12 tabel. (3) Database dikonfirmasi **MySQL/MariaDB**. (4) Autentikasi dikonfirmasi **Laravel Breeze (Blade stack)**. (5) Password default akun baru ditetapkan `"password"`. (6) **Periode evaluasi ditegakkan tunggal** — hanya satu `evaluation_periods` boleh berstatus `open` di satu waktu; membuka periode baru otomatis menutup periode `open` sebelumnya (§6.2, §7.7). |