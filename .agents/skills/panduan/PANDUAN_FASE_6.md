# Panduan Fase 6 — ClassPromotionService (Promosi Kelas Tahunan)

Panduan ngoding manual untuk Fase 6 di [TODO.md](TODO.md). Implementasi **PRD.md §6.1** (proses promosi kelas tahunan) & §2.3 (konsep kohort kelas). Fase ini **backend-first**: service class + Artisan command + test; GUIDELINE hanya menyentuh bagian opsional (tombol admin, Commit 4).

Skill: **laravel-best-practices** (service/action class, DB transaction, Artisan command, arsitektur §15) — diterapkan langsung.

## Konsep (PRD §6.1) yang diterjemahkan ke kode

Kelas = **kohort yang naik tingkat bersama**. Sekali per tahun ajaran, dijalankan promosi:

1. Ambil semua `class_groups` di tahun ajaran asal (`fromYear`).
2. Kelas yang `year_level`-nya sudah **maksimum prodi** (D3→3, D4→4) → **skip (lulus)**, tidak dinaikkan.
3. Kelas lain → buat `class_groups` baru: `year_level + 1`, `class_letter` sama, `class_code` di-regenerate, di `toYear`.
4. Pindahkan mahasiswa **`status=aktif`** ke kelas baru + `current_semester += 2`.
5. Mahasiswa **`cuti`** tidak ikut (menunggu assignment manual saat aktif kembali) → tetap di kelas lama.
6. Mahasiswa **`DO`** tetap di kelas terakhir (histori).

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Kelas lama tidak dihapus** — DO/cuti tetap tertinggal di sana untuk histori (sesuai §2.3). Hanya mahasiswa aktif yang berpindah.
2. **Status mahasiswa tahun akhir tidak otomatis diubah ke `lulus`** — PRD §6.1 hanya menyatakan kelasnya tidak dinaikkan, bukan mengubah status. Penandaan kelulusan dianggap aksi admin terpisah. (Kalau Anda mau otomatis, tinggal tambahkan 1 baris — dicatat di bawah.)
3. **Idempotent** — aman dijalankan dua kali: kelas tujuan dibuat via `firstOrCreate`, dan mahasiswa aktif yang sudah pindah tidak lagi tercakup query kelas lama, jadi tidak ada kenaikan ganda.
4. **`promote()` mengembalikan ringkasan** (array jumlah) supaya command/tombol admin bisa menampilkannya.
5. **Maks tahun prodi = `total_semesters / 2`** (D3: 6/2=3, D4: 8/2=4).

---

## Peta Commit (4 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | ClassPromotionService | logika promosi (§6.1) dalam DB transaction, idempotent |
| 2 | Command `class:promote` | wrapper CLI memanggil service |
| 3 | Test skenario | aktif naik, cuti/DO tetap, lulus, idempotent, command |
| 4 | (Opsional) tombol admin + TODO | UI pemicu (GUIDELINE) + finalisasi checklist |

---

## Commit 1 — ClassPromotionService

```bash
php artisan make:class Services/ClassPromotionService --no-interaction
```

**`app/Services/ClassPromotionService.php`**:

```php
<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use Illuminate\Support\Facades\DB;

class ClassPromotionService
{
    /**
     * Naikkan seluruh kelas dari satu tahun ajaran ke tahun berikutnya (PRD §6.1).
     * Aman dijalankan berulang (idempotent).
     *
     * @return array{classes_promoted:int, classes_graduated:int, students_promoted:int}
     */
    public function promote(string $fromAcademicYear, string $toAcademicYear): array
    {
        return DB::transaction(function () use ($fromAcademicYear, $toAcademicYear): array {
            $summary = ['classes_promoted' => 0, 'classes_graduated' => 0, 'students_promoted' => 0];

            $classes = ClassGroup::with('studyProgram')
                ->where('academic_year', $fromAcademicYear)
                ->get();

            foreach ($classes as $class) {
                $maxYear = intdiv($class->studyProgram->total_semesters, 2);

                // (2) Kelas tahun akhir → lulus, tidak dinaikkan.
                if ($class->year_level >= $maxYear) {
                    $summary['classes_graduated']++;

                    continue;
                }

                // (3) Buat/temukan kelas tujuan (idempotent).
                $newYearLevel = $class->year_level + 1;
                $newClassCode = $class->studyProgram->code.$newYearLevel.$class->class_letter;

                $newClass = ClassGroup::firstOrCreate(
                    ['academic_year' => $toAcademicYear, 'class_code' => $newClassCode],
                    [
                        'study_program_id' => $class->study_program_id,
                        'year_level' => $newYearLevel,
                        'class_letter' => $class->class_letter,
                        'capacity' => $class->capacity,
                    ],
                );
                $summary['classes_promoted']++;

                // (4) Pindahkan mahasiswa aktif + tambah 2 semester.
                //     (5)(6) cuti & DO tidak ikut karena difilter di sini.
                $class->students()
                    ->where('status', StudentStatus::Aktif)
                    ->get()
                    ->each(function ($student) use ($newClass, &$summary): void {
                        $student->update([
                            'class_group_id' => $newClass->id,
                            'current_semester' => $student->current_semester + 2,
                        ]);
                        $summary['students_promoted']++;
                    });
            }

            return $summary;
        });
    }
}
```

> Kalau Anda ingin status tahun akhir otomatis jadi `lulus`, di blok `if ($class->year_level >= $maxYear)` tambahkan: `$class->students()->where('status', StudentStatus::Aktif)->update(['status' => StudentStatus::Lulus]);` sebelum `continue;`. **Panduan ini sengaja tidak melakukannya** (lihat keputusan desain #2).

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/ClassPromotionService.php
git commit -m "Fase 6: ClassPromotionService (promosi kelas tahunan §6.1)

Naikkan kelas fromYear->toYear dalam DB transaction: kelas tahun akhir
di-skip (lulus), lainnya dibuat ulang year_level+1 dengan class_code
di-regenerate; mahasiswa aktif dipindah + current_semester +=2, cuti/DO
tetap. Idempotent via firstOrCreate. Mengembalikan ringkasan jumlah.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Command `class:promote`

```bash
php artisan make:command PromoteClasses --no-interaction
```

**`app/Console/Commands/PromoteClasses.php`**:

```php
<?php

namespace App\Console\Commands;

use App\Services\ClassPromotionService;
use Illuminate\Console\Command;

class PromoteClasses extends Command
{
    /**
     * @var string
     */
    protected $signature = 'class:promote
                            {fromYear : Tahun ajaran asal (mis. 2025/2026)}
                            {toYear : Tahun ajaran tujuan (mis. 2026/2027)}';

    /**
     * @var string
     */
    protected $description = 'Naikkan tingkat semua kelas dari satu tahun ajaran ke tahun berikutnya (PRD §6.1)';

    public function handle(ClassPromotionService $service): int
    {
        $from = (string) $this->argument('fromYear');
        $to = (string) $this->argument('toYear');

        if ($from === $to) {
            $this->error('Tahun asal dan tujuan tidak boleh sama.');

            return self::FAILURE;
        }

        $this->info("Menjalankan promosi kelas: {$from} → {$to} ...");

        $summary = $service->promote($from, $to);

        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Kelas dinaikkan', $summary['classes_promoted']],
                ['Kelas lulus (tidak dinaikkan)', $summary['classes_graduated']],
                ['Mahasiswa dipindahkan', $summary['students_promoted']],
            ],
        );

        $this->info('Selesai.');

        return self::SUCCESS;
    }
}
```

> Service di-inject lewat type-hint di `handle()` (Laravel meresolusi otomatis). Command otomatis terdaftar di Laravel 11+ (auto-discovery folder `app/Console/Commands`).

Uji manual:

```bash
php artisan migrate:fresh --seed
php artisan class:promote 2025/2026 2026/2027
```

Harus menampilkan tabel ringkasan tanpa error.

```bash
vendor/bin/pint --dirty --format agent
git add app/Console/Commands/PromoteClasses.php
git commit -m "Fase 6: command class:promote

Wrapper CLI memanggil ClassPromotionService dan menampilkan ringkasan
(kelas naik / lulus / mahasiswa dipindah). Service di-inject via handle().

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Test skenario promosi

```bash
php artisan make:test ClassPromotionTest --pest --no-interaction
```

**`tests/Feature/ClassPromotionTest.php`**:

```php
<?php

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Services\ClassPromotionService;

/**
 * Helper: buat prodi + 1 kelas di tahun asal.
 */
function makeClass(int $yearLevel = 1, string $degree = 'D3', int $totalSemesters = 6): ClassGroup
{
    $prodi = StudyProgram::factory()->create([
        'code' => 'MI', 'degree_level' => $degree, 'total_semesters' => $totalSemesters,
    ]);

    return ClassGroup::factory()->create([
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => $yearLevel,
        'class_letter' => 'A',
        'class_code' => "MI{$yearLevel}A",
    ]);
}

test('mahasiswa aktif naik kelas dan semester bertambah 2', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    $newClass = ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->first();
    expect($newClass)->not->toBeNull()
        ->and($newClass->year_level)->toBe(2);

    $student->refresh();
    expect($student->class_group_id)->toBe($newClass->id)
        ->and($student->current_semester)->toBe(3);
});

test('mahasiswa cuti tidak ikut dipindah', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Cuti,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('mahasiswa DO tetap di kelas terakhir', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::DO,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('kelas tahun akhir tidak dinaikkan (lulus)', function () {
    $class = makeClass(yearLevel: 3, degree: 'D3', totalSemesters: 6); // D3 maks tahun 3
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 5,
        'status' => StudentStatus::Aktif,
    ]);

    $summary = app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($summary['classes_graduated'])->toBe(1)
        ->and($summary['classes_promoted'])->toBe(0)
        ->and(ClassGroup::where('academic_year', '2026/2027')->exists())->toBeFalse();

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('D4 boleh naik sampai tahun 4', function () {
    $class = makeClass(yearLevel: 3, degree: 'D4', totalSemesters: 8); // D4 maks tahun 4
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 5,
        'status' => StudentStatus::Aktif,
    ]);

    $summary = app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($summary['classes_promoted'])->toBe(1)
        ->and(ClassGroup::where('class_code', 'MI4A')->exists())->toBeTrue();
    expect($student->fresh()->current_semester)->toBe(7);
});

test('promosi idempotent bila dijalankan dua kali', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    $service = app(ClassPromotionService::class);
    $service->promote('2025/2026', '2026/2027');
    $service->promote('2025/2026', '2026/2027');

    // Kelas tujuan tidak dobel; semester hanya +2 sekali (bukan +4).
    expect(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->count())->toBe(1);
    expect($student->fresh()->current_semester)->toBe(3);
});

test('command class:promote berjalan dan memindahkan mahasiswa', function () {
    $class = makeClass(yearLevel: 1);
    Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    $this->artisan('class:promote', ['fromYear' => '2025/2026', 'toYear' => '2026/2027'])
        ->assertSuccessful();

    expect(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->exists())->toBeTrue();
});
```

Jalankan:

```bash
php artisan test --compact --filter=ClassPromotionTest
```

Semua harus hijau, lalu pastikan seluruh suite tetap hijau:

```bash
php artisan test --compact
```

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/ClassPromotionTest.php
git commit -m "Fase 6: test skenario promosi kelas

Uji mahasiswa aktif naik (+2 sem), cuti & DO tetap, kelas tahun akhir
lulus (D3 tahun 3, D4 tahun 4), idempotensi, dan command class:promote.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — (Opsional) Tombol admin pemicu promosi + finalisasi TODO

Bagian ini yang menyentuh **GUIDELINE** (pakai komponen dari Fase 5). Boleh dilewati kalau cukup lewat command CLI.

```bash
php artisan make:controller Admin/ClassPromotionController --no-interaction
```

**`app/Http/Controllers/Admin/ClassPromotionController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ClassPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassPromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.class-promotion.index');
    }

    public function run(Request $request, ClassPromotionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'from_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'to_year' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'different:from_year'],
        ], [
            'from_year.regex' => 'Format tahun ajaran: 2025/2026.',
            'to_year.regex' => 'Format tahun ajaran: 2026/2027.',
            'to_year.different' => 'Tahun tujuan harus berbeda dari tahun asal.',
        ]);

        $summary = $service->promote($validated['from_year'], $validated['to_year']);

        return back()->with('success', sprintf(
            'Promosi %s → %s selesai: %d kelas naik, %d mahasiswa dipindah, %d kelas lulus.',
            $validated['from_year'], $validated['to_year'],
            $summary['classes_promoted'], $summary['students_promoted'], $summary['classes_graduated'],
        ));
    }
}
```

**Route** (`routes/web.php`, grup admin + import):

```php
use App\Http\Controllers\Admin\ClassPromotionController;

Route::get('class-promotion', [ClassPromotionController::class, 'index'])->name('class-promotion.index');
Route::post('class-promotion', [ClassPromotionController::class, 'run'])->name('class-promotion.run');
```

**Tambahkan link sidebar** — di `resources/views/components/admin-layout.blade.php`, tambahkan ke array `$navItems` (mis. setelah item Penugasan Dosen):

```php
['route' => 'admin.class-promotion.index', 'label' => 'Promosi Kelas', 'pattern' => 'admin.class-promotion.*'],
```

**View `resources/views/admin/class-promotion/index.blade.php`**:

```blade
<x-admin-layout header="Promosi Kelas Tahunan">
    <x-card class="max-w-xl">
        <p class="text-sm text-muted">
            Menaikkan semua kelas dari tahun ajaran asal ke tahun berikutnya. Mahasiswa aktif naik tingkat
            (+2 semester); mahasiswa cuti & DO tetap di kelas lama. Kelas tahun akhir tidak dinaikkan.
            Aman dijalankan berulang.
        </p>

        <form method="POST" action="{{ route('admin.class-promotion.run') }}" class="mt-6 space-y-4"
            onsubmit="return confirm('Jalankan promosi kelas? Tindakan ini memindahkan mahasiswa aktif ke tahun berikutnya.')">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="from_year" :value="'Tahun Ajaran Asal'" />
                    <x-text-input id="from_year" name="from_year" class="mt-1 font-mono"
                        :value="old('from_year', '2025/2026')" required />
                    <x-input-error :messages="$errors->get('from_year')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="to_year" :value="'Tahun Ajaran Tujuan'" />
                    <x-text-input id="to_year" name="to_year" class="mt-1 font-mono"
                        :value="old('to_year', '2026/2027')" required />
                    <x-input-error :messages="$errors->get('to_year')" class="mt-1" />
                </div>
            </div>

            <x-button type="submit">Jalankan Promosi</x-button>
        </form>
    </x-card>
</x-admin-layout>
```

Uji manual: login admin → menu "Promosi Kelas" → isi tahun → jalankan → notifikasi ringkasan muncul.

Terakhir, **update TODO.md**: centang semua item Fase 6 jadi `[x]`, update baris status:

> *Fase 0–6 selesai. Fase 6: ClassPromotionService (§6.1) — kelas naik tingkat, mahasiswa aktif dipindah +2 sem, cuti/DO tetap, kelas tahun akhir lulus, idempotent; command class:promote + tombol admin; test skenario hijau. Siap lanjut Fase 7 (Modul Mahasiswa).*

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/ClassPromotionController.php routes/web.php resources/views/components/admin-layout.blade.php resources/views/admin/class-promotion/ TODO.md
git commit -m "Fase 6: tombol admin promosi kelas + finalisasi TODO

Halaman admin pemicu ClassPromotionService (validasi tahun, konfirmasi,
ringkasan hasil) memakai komponen GUIDELINE. Fase 6 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **Import lengkap**: `StudentStatus`, `ClassGroup`, `DB` di service; `ClassPromotionService` di command & controller; controller di `web.php`.
- [ ] **`intdiv($totalSemesters, 2)`** untuk maks tahun (D3→3, D4→4) — bukan pembagian float.
- [ ] **`firstOrCreate`** (bukan `create`) untuk idempotensi kelas tujuan.
- [ ] **Filter `status = Aktif`** saat memindah mahasiswa — inilah yang mengecualikan cuti & DO.
- [ ] **`current_semester + 2`** (naik satu tahun = 2 semester), bukan +1.
- [ ] Command otomatis terdaftar (auto-discovery) — tidak perlu registrasi manual di Laravel 11+.
- [ ] `vendor/bin/pint --dirty --format agent` sebelum tiap commit.

> File panduan ini boleh dihapus setelah Fase 6 kelar (opsional): `git rm PANDUAN_FASE_6.md`.
