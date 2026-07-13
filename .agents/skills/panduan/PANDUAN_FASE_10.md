# Panduan Fase 10 — Polish, Validasi Menyeluruh & Testing E2E

Panduan ngoding manual untuk Fase 10 (final) di [TODO.md](TODO.md). Fase ini **bukan fitur baru** — audit menyeluruh terhadap [GUIDELINE.md](GUIDELINE.md) & [PRD.md](PRD.md), penambahan test E2E/keamanan, dan finalisasi produksi.

Skill: **pest-testing** (E2E, arch test, browser smoke test Pest 4), **laravel-best-practices** (security/mass-assignment), **tailwindcss-development** (audit motion/aksesibilitas) — diterapkan.

## Sifat fase ini

Sebagian besar item Fase 10 adalah **audit (verifikasi)** — Anda menelusuri halaman/kode dengan checklist di panduan ini dan memperbaiki bila ada yang menyimpang. Yang berupa **kode konkret**: CSS `prefers-reduced-motion`, test E2E, test keamanan, arch test, (opsional) browser smoke test, lalu `pint` + `build` final.

---

## Peta Commit (4 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | Motion + audit UI/aksesibilitas | CSS `prefers-reduced-motion` (§8) + checklist audit §2/§3/§4/§9/§10/§12 |
| 2 | Test E2E | alur penuh admin→mahasiswa→dosen, team teaching, promosi kelas |
| 3 | Test keamanan + arch | anti-kebocoran `student_id`, guard role, arch test |
| 4 | Finalisasi (+ browser smoke opsional) | pint seluruh kode, build, update TODO — project selesai |

---

## Commit 1 — Motion polish + audit UI/aksesibilitas

### Tambah `prefers-reduced-motion` ke `resources/css/app.css` (GUIDELINE §8)

Di paling bawah file (setelah `[x-cloak]`):

```css
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}
```

### Checklist audit (telusuri tiap halaman, centang bila sudah sesuai)

**Token warna (§2)** — `grep` cepat untuk menemukan pelanggaran:

```bash
# Cari warna Tailwind default yang seharusnya token GUIDELINE (harus kosong / minimal):
grep -rn "text-gray-\|bg-gray-\|bg-indigo\|text-indigo\|bg-blue-\|text-red-[0-9]" resources/views/
```

- [ ] Accent teal (`bg-accent`/`text-accent`) hanya di elemen interaktif (tombol/link/tab aktif) — bukan dekorasi.
- [ ] Amber (`text-rating`/`bg-rating`) hanya di gauge/skor.
- [ ] Warna status (success/warning/danger) hanya di badge/notifikasi.
- [ ] Latar halaman `bg-canvas`, kartu `bg-surface`, garis `border-border`.

**Tipografi (§3)**
- [ ] Judul pakai `font-display` (Space Grotesk); body default `font-body`.
- [ ] NIM, kode kelas/MK, ID **selalu** `font-mono` — cek di tabel, chip, dropdown, dan detail.

**Layout & responsif (§4.1, §10)**
- [ ] Admin/dosen/kaprodi: sidebar tetap di desktop (≥1024px), toggle di mobile.
- [ ] Mahasiswa: form single-column, bottom-nav di mobile, gauge mudah ditekan.

**Aksesibilitas (§9)** — sebagian besar sudah dari komponen fase sebelumnya, verifikasi:
- [ ] Semua tombol/notch gauge fokusable & punya `focus:ring-accent` (cek `x-button`, `x-rating-gauge`).
- [ ] Gauge interaktif bisa Tab + Enter/Space (sudah di `x-rating-gauge`).
- [ ] Badge status selalu ada **label teks**, bukan warna saja (sudah di `x-badge-status`).
- [ ] Target sentuh ≥44px pada gauge (`size-11`) & tombol mobile.

**Copy/microcopy (§12)**
- [ ] Label tombol kata kerja konkret ("Kirim Evaluasi", "Simpan Perubahan"), bukan "Submit/OK".
- [ ] Notifikasi konsisten dengan aksi ("Evaluasi terkirim", "Program studi ditambahkan").
- [ ] Pesan error ramah & spesifik (Bahasa Indonesia).

Rebuild aset & cek visual di browser (light/dark bila perlu):

```bash
npm run build
```

```bash
git add resources/css/app.css
git commit -m "Fase 10: prefers-reduced-motion + audit konsistensi UI

Matikan transisi non-esensial saat prefers-reduced-motion (§8). Audit
token warna/tipografi/aksesibilitas/copy terhadap GUIDELINE.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

> Bila audit menemukan pelanggaran (mis. `text-gray-500` tersisa), perbaiki ke token yang benar dan ikutkan di commit ini.

---

## Commit 2 — Test E2E (alur penuh)

```bash
php artisan make:test EndToEndFlowTest --pest --no-interaction
```

**`tests/Feature/EndToEndFlowTest.php`**:

```php
<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

test('alur penuh: admin assign → mahasiswa isi → dosen lihat hasil', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]);
    $lecturer = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $period = EvaluationPeriod::factory()->open()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);
    $students = Student::factory()->count(5)->create([
        'study_program_id' => $prodi->id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
    ]);

    // 1) Admin membuat penugasan dosen
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [
        'course_id' => $course->id,
        'lecturer_id' => $lecturer->id,
        'class_group_id' => $class->id,
        'evaluation_period_id' => $period->id,
    ])->assertRedirect();

    $assignment = CourseClassAssignment::firstOrFail();

    // 2) 5 mahasiswa mengisi evaluasi (semua rating 5)
    foreach ($students as $student) {
        $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
            'answers' => $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all(),
            'impression_text' => 'Pengajaran bagus.',
            'suggestion_text' => 'Pertahankan.',
        ])->assertRedirect(route('student.evaluations.index'));
    }

    // 3) Dosen melihat hasil — skor & kesan tampil (threshold 5 terpenuhi)
    $this->actingAs($lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('5.0')
        ->assertSee('Anonim');
});

test('alur team teaching: 1 MK 2 dosen tampil sebagai 2 form', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]);
    $period = EvaluationPeriod::factory()->open()->create();
    $dosenA = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $dosenB = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $student = Student::factory()->create(['study_program_id' => $prodi->id, 'class_group_id' => $class->id, 'current_semester' => 1]);

    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id];
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenA->id])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenB->id])->assertRedirect();

    // Mahasiswa melihat 2 kartu (nama kedua dosen)
    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertOk()
        ->assertSee($dosenA->name)
        ->assertSee($dosenB->name);

    expect(CourseClassAssignment::count())->toBe(2);
});

test('alur promosi kelas menaikkan mahasiswa aktif', function () {
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    $class = ClassGroup::factory()->create([
        'study_program_id' => $prodi->id, 'academic_year' => '2025/2026',
        'year_level' => 1, 'class_letter' => 'A', 'class_code' => 'MI1A',
    ]);
    $student = Student::factory()->create([
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id, 'current_semester' => 1,
    ]);

    $this->artisan('class:promote', ['fromYear' => '2025/2026', 'toYear' => '2026/2027'])->assertSuccessful();

    $newClass = ClassGroup::where('class_code', 'MI2A')->where('academic_year', '2026/2027')->firstOrFail();
    $student->refresh();
    expect($student->class_group_id)->toBe($newClass->id)
        ->and($student->current_semester)->toBe(3);
});
```

```bash
php artisan test --compact --filter=EndToEndFlowTest
git add tests/Feature/EndToEndFlowTest.php
git commit -m "Fase 10: test E2E alur penuh

Alur admin assign → 5 mahasiswa isi → dosen lihat skor+kesan; team
teaching (2 dosen = 2 form); promosi kelas via command.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Test keamanan + arch test

```bash
php artisan make:test SecurityTest --pest --no-interaction
php artisan make:test ArchTest --pest --no-interaction
```

**`tests/Feature/SecurityTest.php`**:

```php
<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

/**
 * Assignment + 5 evaluasi berisi kesan/saran.
 */
function assignmentWithResults(): CourseClassAssignment
{
    $assignment = CourseClassAssignment::factory()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);

    for ($i = 0; $i < 5; $i++) {
        $student = Student::factory()->create(['class_group_id' => $assignment->class_group_id]);
        $eval = Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
        foreach ($questions as $q) {
            EvaluationAnswer::factory()->create(['evaluation_id' => $eval->id, 'evaluation_question_id' => $q->id, 'star_rating' => 4]);
        }
        EvaluationImpression::factory()->create(['evaluation_id' => $eval->id, 'impression_text' => 'Kesan', 'suggestion_text' => 'Saran']);
    }

    return $assignment;
}

test('identitas mahasiswa tidak bocor di endpoint dosen', function () {
    $assignment = assignmentWithResults();
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('identitas mahasiswa tidak bocor di endpoint kaprodi', function () {
    $assignment = assignmentWithResults();
    $kaprodi = User::factory()->kaprodi()->create([
        'study_program_id' => $assignment->classGroup->study_program_id,
    ]);
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('tamu diarahkan ke login di semua area terproteksi', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with([
    '/admin/dashboard',
    '/lecturer/dashboard',
    '/kaprodi/dashboard',
    '/student/evaluations',
]);

test('role tidak bisa mengakses area role lain', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/lecturer/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/kaprodi/dashboard')->assertForbidden();
});
```

**`tests/Feature/ArchTest.php`**:

```php
<?php

arch('tidak ada debug statement tertinggal')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('controller diberi suffix Controller')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('form request diberi suffix Request')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('enum di namespace App\Enums adalah enum')
    ->expect('App\Enums')
    ->toBeEnums();
```

> Opsional (audit keamanan lebih dalam): `arch()->preset()->security();` — tapi ia menandai `rand()`; bila EvaluationSeeder Anda memakai `rand(3, 5)`, ganti dulu ke `fake()->numberBetween(3, 5)` agar lolos, atau lewati preset ini.

### Checklist audit keamanan (verifikasi manual)

- [ ] **`student_id`**: `grep -rn "student_id" resources/views/` harus kosong — view tak pernah render/expose kolom ini.
- [ ] Query kesan & saran hanya lewat `AssignmentResultService` (select eksplisit, tanpa `student_id`).
- [ ] Semua route non-publik ada di grup `middleware(['auth', 'role:...'])`.
- [ ] Setiap model punya `#[Fillable([...])]` (mass-assignment aman) — tak ada `$guarded = []`.
- [ ] `.env` tidak ter-commit (cek `.gitignore`).

```bash
php artisan test --compact --filter="SecurityTest|ArchTest"
git add tests/Feature/SecurityTest.php tests/Feature/ArchTest.php
git commit -m "Fase 10: test keamanan + arch

Anti-kebocoran student_id (dosen & kaprodi), guest ditolak & role tak
lintas-area; arch test (no debug leftover, suffix controller/request,
enum). Audit mass-assignment & anonimitas.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Finalisasi (+ browser smoke opsional)

### (Opsional) Browser smoke test — Pest 4

Butuh plugin browser + Playwright. **Lewati bila tak ingin setup browser.**

```bash
composer require pestphp/pest-plugin-browser --dev --no-interaction
npm install
npx playwright install
php artisan make:test PageSmokeTest --pest --no-interaction
```

Pindahkan file ke `tests/Browser/PageSmokeTest.php` lalu isi:

```php
<?php

use App\Models\User;

test('halaman utama admin tanpa error JavaScript', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/admin/dashboard')
        ->assertNoJavaScriptErrors()
        ->assertSee('SIEDU');
});

test('halaman login tanpa error JavaScript', function () {
    visit('/login')
        ->assertNoJavaScriptErrors()
        ->assertSee('SIEDU');
});
```

> Browser test butuh server berjalan (`php artisan serve`) & Playwright terpasang. Bila lingkungan tak mendukung, cukup andalkan test HTTP di Commit 2–3.

### Finalisasi produksi

```bash
# 1) Format seluruh PHP (bukan hanya --dirty)
vendor/bin/pint --format agent

# 2) Seluruh test hijau
php artisan test --compact

# 3) Build aset produksi
npm run build
```

Pastikan **semua hijau** dan build sukses.

**Update TODO.md** — centang semua item Fase 10, tandai project selesai:

> *Fase 0–10 selesai. Project SIEDU rampung: 12 tabel, 4 role (admin/dosen/mahasiswa/kaprodi), evaluasi anonim dengan Rating Gauge, team teaching, promosi kelas, threshold anonimitas. Test E2E + keamanan + arch hijau; audit GUIDELINE tuntas; aset produksi ter-build.*

```bash
vendor/bin/pint --format agent
git add -A
git commit -m "Fase 10: finalisasi — pint, build, browser smoke (opsional), TODO

Format seluruh kode, seluruh test hijau, aset produksi ter-build.
Project SIEDU selesai (Fase 0–10).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
git push origin master
```

---

## Checklist hal yang mudah terlewat

- [ ] **`prefers-reduced-motion`** di `app.css` lalu `npm run build` (kalau tidak, tak ter-compile).
- [ ] E2E: course `semester` harus cocok `year_level` kelas (§7.1) & satu prodi, kalau tidak `store` assignment gagal validasi.
- [ ] Test keamanan `assertDontSee($student->nim)` — pastikan seeder/factory memberi NIM unik yang tak sengaja muncul di angka lain.
- [ ] `arch()->preset()->security()` menandai `rand()` — ganti ke `fake()->numberBetween()` atau lewati preset.
- [ ] Browser smoke **opsional** — jangan blokir finalisasi bila Playwright tak tersedia.
- [ ] Commit final pakai `pint --format agent` (tanpa `--dirty`) untuk merapikan seluruh kode.

## Setelah Fase 10

Project selesai 🎉. File panduan (`PANDUAN_FASE_*.md`) boleh dihapus dari repo bila tak lagi diperlukan:

```bash
git rm PANDUAN_FASE_*.md
git commit -m "Bersihkan file panduan pengembangan"
```
