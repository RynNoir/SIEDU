# Panduan Fase 3 — Seeder & Factory Data Dummy (SIEDU)

Panduan ngoding manual untuk Fase 3 di [TODO.md](TODO.md). Referensi: [PRD.md](PRD.md) §2.1 (prodi), §5.3 (pertanyaan), §6.1/§7.1 (aturan kelas & semester).

## Konvensi & prasyarat

- Semua model, enum, dan factory Fase 2 sudah ada & lolos test. Seeder di bawah memakainya langsung.
- Seeder dibuat via `php artisan make:seeder NamaSeeder --no-interaction` → namespace otomatis `Database\Seeders`.
- Karena semua seeder satu namespace dengan `DatabaseSeeder`, **tidak perlu `use`** saat menyebut `XxxSeeder::class` di dalam `DatabaseSeeder`.
- **Data referensi tetap** (prodi, pertanyaan, admin, kaprodi, periode) → pakai `create()` eksplisit (tanpa factory, supaya deterministik).
- **Data volume** (kelas, MK, dosen, mahasiswa, assignment, evaluasi) → pakai **factory** dengan relasi/ID di-override eksplisit.

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Cakupan data dummy** — semua 5 prodi dapat cakupan dasar (1 kelas tahun-1 + kaprodi), lalu **prodi MI diberi data terkaya** (3 kelas, team teaching, evaluasi tembus threshold) supaya cukup untuk mengembangkan & menguji Fase 5–9 tanpa membanjiri database.
2. **Akun login dev yang bisa ditebak**: admin (`admin@siedu.test`) & kaprodi (`kaprodi.mi@siedu.test`, dst) pakai email tetap + password `"password"`. Mahasiswa & dosen pakai email acak dari factory (bukan untuk login manual — Anda login sebagai admin/kaprodi).
3. **`must_change_password`**: mengikuti TODO → admin `false`, kaprodi `true`. Mahasiswa & dosen **`false`** (default factory) demi kemudahan dev — alur paksa-ganti-password diuji lewat feature test di Fase 4, bukan lewat data seed. (Di produksi nanti, form admin-create di Fase 5 yang men-set `true`.)
4. **Alignment periode/kelas**: periode aktif = **Ganjil 2025/2026** (`open`), histori = **Ganjil 2024/2025** (`closed`). Kelas academic_year `2025/2026`, mahasiswa di semester ganjil (`current_semester = year_level*2 - 1`).

> Catatan performa: `migrate:fresh --seed` penuh membuat ~200 user + ~175 mahasiswa + evaluasi dummy — perkiraan 10–20 detik. Password di-hash sekali saja (UserFactory meng-cache-nya), jadi tidak lambat.

---

## Urutan Commit (12 commit) — sama dengan urutan dependensi

Urutan ini penting: tiap seeder butuh data dari seeder sebelumnya. `DatabaseSeeder` dirakit **bertahap** — tiap commit menambah 1 seeder + 1 baris di `DatabaseSeeder`, lalu `migrate:fresh --seed` untuk verifikasi bertahap.

| # | Commit | Butuh data dari |
|---|---|---|
| 1 | StudyProgramSeeder | — |
| 2 | AdminSeeder | — |
| 3 | KaprodiSeeder | StudyProgram |
| 4 | ClassGroupSeeder | StudyProgram |
| 5 | CourseSeeder | StudyProgram |
| 6 | LecturerSeeder | StudyProgram |
| 7 | StudentSeeder | StudyProgram, ClassGroup, Admin |
| 8 | EvaluationQuestionSeeder | — |
| 9 | EvaluationPeriodSeeder | — |
| 10 | CourseClassAssignmentSeeder | Course, Lecturer, ClassGroup, Period, Admin |
| 11 | EvaluationSeeder (opsional) | Student, Assignment, Period, Question |
| 12 | Verifikasi + update TODO.md | — |

**Alur kerja tiap commit:**

```bash
# 1. Scaffold
php artisan make:seeder NamaSeeder --no-interaction

# 2. Isi seeder + tambahkan barisnya ke $this->call([...]) di DatabaseSeeder

# 3. Verifikasi (drop semua tabel + seed ulang dari awal)
php artisan migrate:fresh --seed

# 4. Format + commit
vendor/bin/pint --dirty --format agent
git add database/seeders/NamaSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "..."
```

---

## Commit 1 — StudyProgramSeeder

```bash
php artisan make:seeder StudyProgramSeeder --no-interaction
```

**`database/seeders/StudyProgramSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'Manajemen Informatika', 'code' => 'MI', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Teknik Komputer', 'code' => 'TK', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Sistem Informasi', 'code' => 'SI', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Teknologi Rekayasa Perangkat Lunak', 'code' => 'TRPL', 'degree_level' => DegreeLevel::D4, 'total_semesters' => 8],
            ['name' => 'Animasi', 'code' => 'ANIM', 'degree_level' => DegreeLevel::D4, 'total_semesters' => 8],
        ];

        foreach ($programs as $program) {
            StudyProgram::create($program);
        }
    }
}
```

**Edit `database/seeders/DatabaseSeeder.php`** — ganti seluruh isi jadi:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            StudyProgramSeeder::class,
        ]);
    }
}
```

> Baris `User::factory()->create(['Test User'...])` bawaan dihapus — kita pakai AdminSeeder yang deterministik.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/StudyProgramSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: StudyProgramSeeder + rakit DatabaseSeeder

5 prodi persis PRD §2.1 (MI/TK/SI D3-6sem; TRPL/ANIM D4-8sem).
DatabaseSeeder dibersihkan dari Test User bawaan dan mulai memanggil
seeder terstruktur.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — AdminSeeder

```bash
php artisan make:seeder AdminSeeder --no-interaction
```

**`database/seeders/AdminSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@siedu.test',
            'role' => Role::Admin,
            'must_change_password' => false,
        ]);
    }
}
```

> `must_change_password = false` agar admin bisa langsung login untuk setup (TODO Fase 3). Password default factory = `"password"`.

**DatabaseSeeder** — tambahkan `AdminSeeder::class,` setelah `StudyProgramSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/AdminSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: AdminSeeder

1 akun admin (admin@siedu.test / password), must_change_password=false
supaya langsung bisa login untuk setup master data.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — KaprodiSeeder

```bash
php artisan make:seeder KaprodiSeeder --no-interaction
```

**`database/seeders/KaprodiSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KaprodiSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StudyProgram::all() as $prodi) {
            User::factory()->create([
                'name' => "Kaprodi {$prodi->code}",
                'email' => 'kaprodi.'.Str::lower($prodi->code).'@siedu.test',
                'role' => Role::Kaprodi,
                'study_program_id' => $prodi->id,
                'must_change_password' => true,
            ]);
        }
    }
}
```

> 1 kaprodi per prodi, `study_program_id` mengunci cakupan dashboard-nya (PRD §3, dipakai Fase 9). `must_change_password=true` sesuai TODO.

**DatabaseSeeder** — tambahkan `KaprodiSeeder::class,` setelah `AdminSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/KaprodiSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: KaprodiSeeder

1 akun kaprodi per prodi (kaprodi.{code}@siedu.test), study_program_id
diisi untuk membatasi cakupan dashboard ke prodinya (PRD §3, Fase 9).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — ClassGroupSeeder

```bash
php artisan make:seeder ClassGroupSeeder --no-interaction
```

**`database/seeders/ClassGroupSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class ClassGroupSeeder extends Seeder
{
    private const ACADEMIC_YEAR = '2025/2026';

    public function run(): void
    {
        // Semua prodi: 1 kelas tahun-1 (A)
        foreach (StudyProgram::all() as $prodi) {
            $this->makeClass($prodi, 1, 'A');
        }

        // MI diberi data lebih kaya: kelas paralel (B) + kelas tahun-2 (A)
        $mi = StudyProgram::where('code', 'MI')->firstOrFail();
        $this->makeClass($mi, 1, 'B');
        $this->makeClass($mi, 2, 'A');
    }

    private function makeClass(StudyProgram $prodi, int $yearLevel, string $letter): void
    {
        ClassGroup::create([
            'study_program_id' => $prodi->id,
            'academic_year' => self::ACADEMIC_YEAR,
            'year_level' => $yearLevel,
            'class_letter' => $letter,
            // class_code = {KODE_PRODI}{TAHUN}{HURUF} (PRD §2.2)
            'class_code' => "{$prodi->code}{$yearLevel}{$letter}",
            'capacity' => 25,
        ]);
    }
}
```

> Menghasilkan: `MI1A`, `TK1A`, `SI1A`, `TRPL1A`, `ANIM1A`, plus `MI1B` (paralel) & `MI2A`. Total 7 kelas.

**DatabaseSeeder** — tambahkan `ClassGroupSeeder::class,` setelah `KaprodiSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/ClassGroupSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: ClassGroupSeeder

Kelas tahun-1 untuk 5 prodi + kelas paralel MI1B & kelas tahun-2 MI2A
(uji filter kelas paralel). class_code mengikuti format PRD §2.2.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 5 — CourseSeeder

```bash
php artisan make:seeder CourseSeeder --no-interaction
```

**`database/seeders/CourseSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Semester ganjil yang punya kelas di seeder ini: 1 (tahun 1) & 3 (tahun 2)
        foreach (StudyProgram::all() as $prodi) {
            foreach ([1, 3] as $semester) {
                Course::factory()->count(4)->create([
                    'study_program_id' => $prodi->id,
                    'semester' => $semester,
                ]);
            }
        }
    }
}
```

> 4 MK per (prodi × semester) — nama/kode/SKS di-generate factory. Cukup untuk mengisi assignment. Semester dibatasi ke ganjil (1 & 3) agar cocok dengan `year_level` kelas yang ada (PRD §7.1).

**DatabaseSeeder** — tambahkan `CourseSeeder::class,` setelah `ClassGroupSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/CourseSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: CourseSeeder

4 MK per prodi per semester ganjil (1 & 3), sesuai year_level kelas
yang ada (PRD §7.1). Nama/kode/SKS via factory.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 6 — LecturerSeeder

```bash
php artisan make:seeder LecturerSeeder --no-interaction
```

**`database/seeders/LecturerSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class LecturerSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StudyProgram::all() as $prodi) {
            Lecturer::factory()->count(4)->create([
                'study_program_id' => $prodi->id,
            ]);
        }
    }
}
```

> 4 dosen/prodi. `LecturerFactory` otomatis membuat `user` role=lecturer + NIP unik. `study_program_id` di-override ke prodi asli (bukan prodi baru dari factory).

**DatabaseSeeder** — tambahkan `LecturerSeeder::class,` setelah `CourseSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/LecturerSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: LecturerSeeder

4 dosen per prodi, tiap dosen punya user role=lecturer + NIP unik.
Cukup untuk skenario team teaching (butuh >=2 dosen/prodi).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 7 — StudentSeeder

```bash
php artisan make:seeder StudentSeeder --no-interaction
```

**`database/seeders/StudentSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', Role::Admin)->firstOrFail();

        foreach (ClassGroup::all() as $class) {
            Student::factory()->count(25)->create([
                'study_program_id' => $class->study_program_id,
                'class_group_id' => $class->id,
                // Semester ganjil: tahun 1 -> sem 1, tahun 2 -> sem 3 (PRD §7.1)
                'current_semester' => $class->year_level * 2 - 1,
                'created_by' => $admin->id,
            ]);
        }
    }
}
```

> Penting: `created_by` di-override ke ID admin asli — kalau tidak, `StudentFactory` bikin admin baru untuk tiap mahasiswa (175 admin sampah!). `current_semester` diselaraskan dengan `year_level` kelas.

**DatabaseSeeder** — tambahkan `StudentSeeder::class,` setelah `LecturerSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/StudentSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: StudentSeeder

25 mahasiswa per kelas (status aktif), current_semester selaras
year_level (PRD §7.1). created_by di-set ke admin asli agar tidak
membuat admin duplikat. NIM & user unik per mahasiswa.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 8 — EvaluationQuestionSeeder

```bash
php artisan make:seeder EvaluationQuestionSeeder --no-interaction
```

**`database/seeders/EvaluationQuestionSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Models\EvaluationQuestion;
use Illuminate\Database\Seeder;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Kategori => daftar pertanyaan (PRD §5.3). Semua format
        // "Bagaimana penilaian Anda terhadap ..." agar skala bintang 1-5 konsisten.
        $groups = [
            'Penguasaan & Penyampaian Materi' => [
                'Bagaimana penilaian Anda terhadap penguasaan dosen terhadap materi yang diajarkan?',
                'Bagaimana penilaian Anda terhadap kejelasan dosen dalam menyampaikan materi?',
                'Bagaimana penilaian Anda terhadap relevansi contoh yang diberikan dosen dalam menjelaskan materi?',
            ],
            'Interaksi & Ketersediaan' => [
                'Bagaimana penilaian Anda terhadap keterbukaan dosen dalam menerima pertanyaan/diskusi?',
                'Bagaimana penilaian Anda terhadap kemudahan menghubungi dosen di luar jam kelas?',
            ],
            'Kedisiplinan & Profesionalisme' => [
                'Bagaimana penilaian Anda terhadap kedisiplinan dan ketepatan waktu dosen?',
                'Bagaimana penilaian Anda terhadap kejelasan informasi dosen jika ada perubahan jadwal?',
            ],
            'Penilaian & Feedback' => [
                'Bagaimana penilaian Anda terhadap kejelasan kriteria penilaian tugas/ujian?',
                'Bagaimana penilaian Anda terhadap kualitas feedback yang diberikan dosen atas tugas Anda?',
            ],
            'Rangkuman Keseluruhan' => [
                'Secara keseluruhan, bagaimana penilaian Anda terhadap kualitas pengajaran dosen ini?',
            ],
        ];

        $order = 1;
        foreach ($groups as $category => $questions) {
            foreach ($questions as $text) {
                EvaluationQuestion::create([
                    'category' => $category,
                    'question_text' => $text,
                    'order_number' => $order++,
                    'is_active' => true,
                ]);
            }
        }
    }
}
```

> 10 pertanyaan, 5 kategori, `order_number` 1–10 berurutan (PRD §5.3).

**DatabaseSeeder** — tambahkan `EvaluationQuestionSeeder::class,` setelah `StudentSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/EvaluationQuestionSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: EvaluationQuestionSeeder

10 pertanyaan template PRD §5.3 dalam 5 kategori, semua format
'Bagaimana penilaian Anda terhadap...', order_number 1-10 berurutan.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 9 — EvaluationPeriodSeeder

```bash
php artisan make:seeder EvaluationPeriodSeeder --no-interaction
```

**`database/seeders/EvaluationPeriodSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Seeder;

class EvaluationPeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Histori (closed) — untuk uji filter perbandingan antar periode.
        EvaluationPeriod::create([
            'name' => 'Ganjil 2024/2025',
            'academic_year' => '2024/2025',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-31',
            'status' => PeriodStatus::Closed,
        ]);

        // Periode aktif (open) — hanya boleh ada 1 open di satu waktu (PRD §7.7).
        EvaluationPeriod::create([
            'name' => 'Ganjil 2025/2026',
            'academic_year' => '2025/2026',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->addWeeks(6),
            'status' => PeriodStatus::Open,
        ]);
    }
}
```

> Hanya 1 periode `open` — konsisten dengan aturan periode tunggal. Yang `closed` jadi histori untuk fitur perbandingan di dashboard dosen (Fase 8).

**DatabaseSeeder** — tambahkan `EvaluationPeriodSeeder::class,` setelah `EvaluationQuestionSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/EvaluationPeriodSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: EvaluationPeriodSeeder

1 periode open (Ganjil 2025/2026) + 1 closed (Ganjil 2024/2025) untuk
histori/uji filter. Mematuhi aturan periode tunggal (hanya 1 open).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 10 — CourseClassAssignmentSeeder

```bash
php artisan make:seeder CourseClassAssignmentSeeder --no-interaction
```

**`database/seeders/CourseClassAssignmentSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseClassAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $period = EvaluationPeriod::open()->firstOrFail();
        $admin = User::where('role', Role::Admin)->firstOrFail();

        foreach (ClassGroup::all() as $class) {
            $semester = $class->year_level * 2 - 1; // ganjil

            $courses = Course::where('study_program_id', $class->study_program_id)
                ->where('semester', $semester)
                ->get();

            $lecturers = Lecturer::where('study_program_id', $class->study_program_id)
                ->get()
                ->values();

            if ($courses->isEmpty() || $lecturers->isEmpty()) {
                continue;
            }

            foreach ($courses as $i => $course) {
                $lecturer = $lecturers[$i % $lecturers->count()];

                $this->assign($course->id, $lecturer->id, $class->id, $period->id, $admin->id);

                // Team teaching (PRD §4.4 v1.1): MK pertama di MI1A diampu 2 dosen.
                if ($class->class_code === 'MI1A' && $i === 0) {
                    $second = $lecturers->firstWhere('id', '!=', $lecturer->id);
                    if ($second) {
                        $this->assign($course->id, $second->id, $class->id, $period->id, $admin->id);
                    }
                }
            }
        }
    }

    private function assign(int $courseId, int $lecturerId, int $classId, int $periodId, int $adminId): void
    {
        CourseClassAssignment::create([
            'course_id' => $courseId,
            'lecturer_id' => $lecturerId,
            'class_group_id' => $classId,
            'evaluation_period_id' => $periodId,
            'created_by' => $adminId,
        ]);
    }
}
```

> Tiap kelas dapat assignment untuk MK prodinya yang cocok semester. **MI1A** punya kasus team teaching (1 MK, 2 dosen) untuk menguji jalur v1.1. Unique constraint 4-kolom tetap aman karena `lecturer_id` berbeda.

**DatabaseSeeder** — tambahkan `CourseClassAssignmentSeeder::class,` setelah `EvaluationPeriodSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/CourseClassAssignmentSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: CourseClassAssignmentSeeder

Assign dosen ke MK+kelas untuk periode aktif, MK cocok semester kelas
(PRD §7.1). Termasuk 1 kasus team teaching (MI1A: 1 MK 2 dosen) untuk
menguji jalur v1.1.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 11 — EvaluationSeeder (opsional, untuk uji dashboard)

```bash
php artisan make:seeder EvaluationSeeder --no-interaction
```

**`database/seeders/EvaluationSeeder.php`**:

```php
<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $questions = EvaluationQuestion::active()->get();

        $mi1a = ClassGroup::where('class_code', 'MI1A')->firstOrFail();

        // Ambil 6 mahasiswa (> threshold 5) supaya kesan & saran tampil di dashboard.
        $students = Student::where('class_group_id', $mi1a->id)->take(6)->get();

        $assignments = CourseClassAssignment::where('class_group_id', $mi1a->id)->get();

        foreach ($assignments as $assignment) {
            foreach ($students as $student) {
                $evaluation = Evaluation::create([
                    'student_id' => $student->id,
                    'course_class_assignment_id' => $assignment->id,
                    'evaluation_period_id' => $assignment->evaluation_period_id,
                    'submitted_at' => now(),
                ]);

                foreach ($questions as $question) {
                    EvaluationAnswer::create([
                        'evaluation_id' => $evaluation->id,
                        'evaluation_question_id' => $question->id,
                        'star_rating' => rand(3, 5),
                    ]);
                }

                EvaluationImpression::create([
                    'evaluation_id' => $evaluation->id,
                    'impression_text' => 'Penjelasan dosen mudah dipahami dan runtut.',
                    'suggestion_text' => 'Tugas bisa diberi contoh pengerjaan lebih banyak.',
                ]);
            }
        }
    }
}
```

> 6 mahasiswa MI1A mengisi evaluasi penuh untuk tiap assignment di kelasnya → melewati threshold ≥5 responden, jadi dashboard dosen (Fase 8) punya data kesan & saran yang tampil. Unique constraint `evaluations` aman (kombinasi student+assignment+periode unik).

**DatabaseSeeder** — tambahkan `EvaluationSeeder::class,` setelah `CourseClassAssignmentSeeder::class,`.

```bash
php artisan migrate:fresh --seed
vendor/bin/pint --dirty --format agent
git add database/seeders/EvaluationSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: EvaluationSeeder (data uji dashboard)

6 mahasiswa MI1A mengisi evaluasi penuh (jawaban + kesan/saran) untuk
tiap assignment di kelasnya, menembus threshold >=5 responden agar
kesan & saran tampil di dashboard dosen (Fase 8).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 12 — Verifikasi akhir + update TODO.md

**DatabaseSeeder final** (pastikan isinya seperti ini):

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            StudyProgramSeeder::class,
            AdminSeeder::class,
            KaprodiSeeder::class,
            ClassGroupSeeder::class,
            CourseSeeder::class,
            LecturerSeeder::class,
            StudentSeeder::class,
            EvaluationQuestionSeeder::class,
            EvaluationPeriodSeeder::class,
            CourseClassAssignmentSeeder::class,
            EvaluationSeeder::class,
        ]);
    }
}
```

Jalankan seed penuh sekali lagi:

```bash
php artisan migrate:fresh --seed
```

**Verifikasi jumlah baris** — buka tinker (read-only, aman):

```bash
php artisan tinker
```

lalu ketik satu per satu:

```php
App\Models\StudyProgram::count();        // 5
App\Models\User::count();                // ~1 admin + 5 kaprodi + 20 dosen + 175 mhs = 201
App\Models\ClassGroup::count();          // 7
App\Models\Course::count();              // 40 (5 prodi x 2 sem x 4)
App\Models\Lecturer::count();            // 20
App\Models\Student::count();             // 175 (7 kelas x 25)
App\Models\EvaluationQuestion::count();  // 10
App\Models\EvaluationPeriod::count();    // 2
App\Models\CourseClassAssignment::count(); // jumlah assignment (termasuk 1 ekstra team teaching)
App\Models\Evaluation::count();          // 6 mhs x jumlah assignment MI1A
```

**Cek team teaching** (harus ada 1 MK di MI1A dengan 2 dosen):

```php
App\Models\CourseClassAssignment::whereHas('classGroup', fn($q) => $q->where('class_code','MI1A'))
    ->get()
    ->groupBy('course_id')
    ->map->count();   // salah satu course_id harus bernilai 2
```

**Cek threshold anonimitas** (assignment MI1A harus punya ≥5 evaluasi):

```php
App\Models\CourseClassAssignment::withCount('evaluations')
    ->whereHas('classGroup', fn($q) => $q->where('class_code','MI1A'))
    ->pluck('evaluations_count');   // nilainya 6 (>= 5)
```

Ketik `exit` untuk keluar tinker.

Kalau semua angka masuk akal, **update TODO.md**: centang semua item Fase 3 jadi `[x]` dan update baris **Status project saat ini** jadi kira-kira:

> *Fase 0–3 selesai. Fase 3: 11 seeder terstruktur (5 prodi, admin, 5 kaprodi, 7 kelas, 40 MK, 20 dosen, 175 mahasiswa, 10 pertanyaan, 2 periode, assignment + team teaching, evaluasi dummy tembus threshold). `migrate:fresh --seed` sukses. Siap lanjut Fase 4 (Autentikasi, Role & Force-Change-Password).*

```bash
vendor/bin/pint --dirty --format agent
git add TODO.md database/seeders/DatabaseSeeder.php
git commit -m "Fase 3: verifikasi seed penuh + update checklist TODO.md

migrate:fresh --seed sukses; jumlah baris, kasus team teaching, dan
threshold anonimitas terverifikasi via tinker. Fase 3 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Lalu push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **`created_by` di StudentSeeder & CourseClassAssignmentSeeder** wajib di-override ke ID admin asli — kalau tidak, factory bikin admin baru tiap baris.
- [ ] **Override semua FK factory di seeder** (study_program_id, class_group_id, dst) — factory default bikin parent baru (prodi/kelas nyasar) kalau tidak di-override.
- [ ] **Urutan `$this->call([...])`** harus ikut dependensi (prodi & admin dulu, evaluasi terakhir).
- [ ] **Enum langsung** boleh dipakai di `create()` (`'degree_level' => DegreeLevel::D3`) — Eloquent handle konversi ke string karena kolomnya di-cast.
- [ ] **`migrate:fresh --seed`** menghapus SEMUA data tiap kali — jangan dipakai kalau ada data penting yang mau dipertahankan.
- [ ] Jalankan **`vendor/bin/pint --dirty --format agent`** sebelum tiap commit.

> File panduan ini boleh dihapus setelah Fase 3 kelar (opsional): `git rm PANDUAN_FASE_3.md`.
