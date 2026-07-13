# Panduan Fase 2 — Models & Relationships (SIEDU)

Panduan ngoding manual untuk Fase 2 di [TODO.md](TODO.md). Referensi skema: [PRD.md](PRD.md) §4.

## Konvensi project yang diikuti (dari `User.php` yang sudah ada)

- Model pakai **PHP attribute** `#[Fillable([...])]` & `#[Hidden([...])]` (bukan properti `$fillable`).
- Cast pakai **method** `casts()`, bukan properti `$casts`.
- Factory pakai helper `fake()` (bukan `$this->faker`).
- Relasi diberi **PHPDoc generic** (`HasMany<Model, $this>`) sesuai stub Laravel modern.

## Dua keputusan desain

1. **Pakai PHP Enum** untuk kolom enum (`role`, `status`, `degree_level`, `semester_type`, period `status`) — didorong aturan PHP CLAUDE.md ("TitleCase untuk Enum keys") dan bikin `casts()` type-safe. Enum di folder baru **`app/Enums/`**.
2. **Commit per model**, enum digabung ke commit model yang memakainya (mis. `DegreeLevel` ikut commit `StudyProgram`), supaya tiap commit self-contained.

> Catatan teknis: referensi `ModelLain::class` di dalam relasi **tidak error** walau model itu belum dibuat (PHP resolve `::class` jadi string, tidak meng-autoload). Jadi urutan commit di bawah aman. Verifikasi menyeluruh dilakukan lewat **Pest test di commit terakhir**.

---

## Peta Commit (13 commit)

| # | Commit | File utama |
|---|---|---|
| 1 | StudyProgram | `DegreeLevel` enum, `StudyProgram`, `StudyProgramFactory` |
| 2 | User (edit) | `Role` enum, `User` (edit), `UserFactory` (edit) |
| 3 | ClassGroup | `ClassGroup`, `ClassGroupFactory` |
| 4 | Course | `Course`, `CourseFactory` |
| 5 | Lecturer | `Lecturer`, `LecturerFactory` |
| 6 | Student | `StudentStatus` enum, `Student`, `StudentFactory` |
| 7 | EvaluationPeriod | `SemesterType` + `PeriodStatus` enum, `EvaluationPeriod`, factory |
| 8 | EvaluationQuestion | `EvaluationQuestion`, factory |
| 9 | CourseClassAssignment | `CourseClassAssignment`, factory |
| 10 | Evaluation | `Evaluation`, factory |
| 11 | EvaluationAnswer | `EvaluationAnswer`, factory |
| 12 | EvaluationImpression | `EvaluationImpression`, factory |
| 13 | Test + TODO | `tests/Feature/ModelRelationshipTest.php`, update `TODO.md` |

**Alur kerja tiap commit:**

```bash
# 1. Scaffold (kecuali User yang cuma diedit)
php artisan make:model NamaModel --factory --no-interaction

# 2. Buat/edit file enum (bila ada), model, dan factory sesuai kode di bawah

# 3. Format
vendor/bin/pint --dirty --format agent

# 4. Stage + commit (lihat pesan commit di tiap bagian)
```

Untuk **enum**, Laravel tidak punya `make:enum`, jadi buat file-nya manual di `app/Enums/`.

---

## Commit 1 — StudyProgram

```bash
php artisan make:model StudyProgram --factory --no-interaction
```

**`app/Enums/DegreeLevel.php`** (buat manual):

```php
<?php

namespace App\Enums;

enum DegreeLevel: string
{
    case D3 = 'D3';
    case D4 = 'D4';

    /**
     * Total semester untuk jenjang ini (PRD §2.1) — D3 = 6, D4 = 8.
     */
    public function totalSemesters(): int
    {
        return match ($this) {
            self::D3 => 6,
            self::D4 => 8,
        };
    }
}
```

**`app/Models/StudyProgram.php`**:

```php
<?php

namespace App\Models;

use App\Enums\DegreeLevel;
use Database\Factories\StudyProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'degree_level', 'total_semesters'])]
class StudyProgram extends Model
{
    /** @use HasFactory<StudyProgramFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'degree_level' => DegreeLevel::class,
            'total_semesters' => 'integer',
        ];
    }

    /**
     * @return HasMany<ClassGroup, $this>
     */
    public function classGroups(): HasMany
    {
        return $this->hasMany(ClassGroup::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * @return HasMany<Lecturer, $this>
     */
    public function lecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class);
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
```

**`database/factories/StudyProgramFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyProgram>
 */
class StudyProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $degree = fake()->randomElement(DegreeLevel::cases());

        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'degree_level' => $degree,
            'total_semesters' => $degree->totalSemesters(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/DegreeLevel.php app/Models/StudyProgram.php database/factories/StudyProgramFactory.php
git commit -m "Fase 2: model StudyProgram + enum DegreeLevel

Model prodi dengan relasi hasMany ke classGroups/courses/lecturers/
students (PRD §4.1). Enum DegreeLevel (D3/D4) dengan helper
totalSemesters() untuk validasi jenjang di fase berikutnya.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — User (edit) + Role enum

Tidak pakai `make:model` (User sudah ada). Edit file yang ada.

**`app/Enums/Role.php`** (buat manual):

```php
<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Lecturer = 'lecturer';
    case Student = 'student';
    case Kaprodi = 'kaprodi';
}
```

**`app/Models/User.php`** (ganti seluruh isi):

```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'must_change_password', 'study_program_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * @return HasOne<Lecturer, $this>
     */
    public function lecturer(): HasOne
    {
        return $this->hasOne(Lecturer::class);
    }

    /**
     * @return HasOne<Student, $this>
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Diisi hanya saat role = kaprodi (PRD §3, revisi v1.2).
     *
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isLecturer(): bool
    {
        return $this->role === Role::Lecturer;
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function isKaprodi(): bool
    {
        return $this->role === Role::Kaprodi;
    }
}
```

**`database/factories/UserFactory.php`** (ganti seluruh isi — tambah default `role` + state per role):

```php
<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Student,
            'must_change_password' => false,
            'study_program_id' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Admin]);
    }

    public function lecturer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Lecturer]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Student]);
    }

    public function kaprodi(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Kaprodi,
            'study_program_id' => StudyProgram::factory(),
        ]);
    }
}
```

> ⚠️ Migration `users` bikin `role` **NOT NULL tanpa default**, jadi factory **wajib** mengisi `role` — itu sebabnya ditambahkan di `definition()`.

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/Role.php app/Models/User.php database/factories/UserFactory.php
git commit -m "Fase 2: perluas model User + enum Role

Cast role (enum) & must_change_password (bool); relasi hasOne
lecturer/student, belongsTo studyProgram (kaprodi); helper
isAdmin/isLecturer/isStudent/isKaprodi. Factory diberi default role
+ state admin/lecturer/student/kaprodi.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — ClassGroup

```bash
php artisan make:model ClassGroup --factory --no-interaction
```

**`app/Models/ClassGroup.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\ClassGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['study_program_id', 'academic_year', 'year_level', 'class_letter', 'class_code', 'capacity'])]
class ClassGroup extends Model
{
    /** @use HasFactory<ClassGroupFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year_level' => 'integer',
            'capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * @return HasMany<CourseClassAssignment, $this>
     */
    public function courseClassAssignments(): HasMany
    {
        return $this->hasMany(CourseClassAssignment::class);
    }
}
```

> Catatan: TODO menyebut "accessor `class_code` bila mau auto-generate". Sengaja **tidak** dibuat accessor karena `class_code` adalah kolom tersimpan — auto-generate akan menimpanya. Pembuatan `class_code` (format `{KODE}{TAHUN}{HURUF}`) ditangani di seeder (Fase 3) & `ClassPromotionService` (Fase 6).

**`database/factories/ClassGroupFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'academic_year' => '2025/2026',
            'year_level' => fake()->numberBetween(1, 4),
            'class_letter' => fake()->randomElement(['A', 'B', 'C']),
            'class_code' => strtoupper(fake()->unique()->bothify('??#?')),
            'capacity' => 25,
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/ClassGroup.php database/factories/ClassGroupFactory.php
git commit -m "Fase 2: model ClassGroup

Relasi belongsTo studyProgram; hasMany students & courseClassAssignments
(PRD §4.2). class_code dibuat di seeder/ClassPromotionService, bukan
accessor (kolom tersimpan).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Course

```bash
php artisan make:model Course --factory --no-interaction
```

**`app/Models/Course.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['study_program_id', 'name', 'code', 'semester', 'credit_hours'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'credit_hours' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return HasMany<CourseClassAssignment, $this>
     */
    public function courseClassAssignments(): HasMany
    {
        return $this->hasMany(CourseClassAssignment::class);
    }
}
```

**`database/factories/CourseFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'semester' => fake()->numberBetween(1, 8),
            'credit_hours' => fake()->numberBetween(2, 4),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Course.php database/factories/CourseFactory.php
git commit -m "Fase 2: model Course

Relasi belongsTo studyProgram; hasMany courseClassAssignments (PRD §4.3).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 5 — Lecturer

```bash
php artisan make:model Lecturer --factory --no-interaction
```

**`app/Models/Lecturer.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\LecturerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'nip', 'study_program_id'])]
class Lecturer extends Model
{
    /** @use HasFactory<LecturerFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return HasMany<CourseClassAssignment, $this>
     */
    public function courseClassAssignments(): HasMany
    {
        return $this->hasMany(CourseClassAssignment::class);
    }
}
```

**`database/factories/LecturerFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->lecturer(),
            'name' => fake()->name(),
            'nip' => fake()->unique()->numerify('##################'),
            'study_program_id' => StudyProgram::factory(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Lecturer.php database/factories/LecturerFactory.php
git commit -m "Fase 2: model Lecturer

Relasi belongsTo user & studyProgram (homebase); hasMany
courseClassAssignments (PRD §4.6).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 6 — Student + StudentStatus enum

```bash
php artisan make:model Student --factory --no-interaction
```

**`app/Enums/StudentStatus.php`**:

```php
<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Aktif = 'aktif';
    case Cuti = 'cuti';
    case DO = 'DO';
    case Lulus = 'lulus';
}
```

**`app/Models/Student.php`**:

```php
<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'nim', 'name', 'study_program_id',
    'class_group_id', 'current_semester', 'status', 'created_by',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_semester' => 'integer',
            'status' => StudentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<StudyProgram, $this>
     */
    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * Admin yang menginput data mahasiswa ini (audit trail).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
```

**`database/factories/StudentFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'nim' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'study_program_id' => StudyProgram::factory(),
            'class_group_id' => ClassGroup::factory(),
            'current_semester' => fake()->numberBetween(1, 8),
            'status' => StudentStatus::Aktif,
            'created_by' => User::factory()->admin(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/StudentStatus.php app/Models/Student.php database/factories/StudentFactory.php
git commit -m "Fase 2: model Student + enum StudentStatus

Relasi belongsTo user/studyProgram/classGroup/creator; hasMany
evaluations (PRD §4.7). Enum StudentStatus (aktif/cuti/DO/lulus)
menentukan perilaku promosi kelas (Fase 6).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 7 — EvaluationPeriod + SemesterType & PeriodStatus enum

```bash
php artisan make:model EvaluationPeriod --factory --no-interaction
```

**`app/Enums/SemesterType.php`**:

```php
<?php

namespace App\Enums;

enum SemesterType: string
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';
}
```

**`app/Enums/PeriodStatus.php`**:

```php
<?php

namespace App\Enums;

enum PeriodStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Closed = 'closed';
}
```

**`app/Models/EvaluationPeriod.php`**:

```php
<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use Database\Factories\EvaluationPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'academic_year', 'semester_type', 'start_date', 'end_date', 'status'])]
class EvaluationPeriod extends Model
{
    /** @use HasFactory<EvaluationPeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester_type' => SemesterType::class,
            'status' => PeriodStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Scope periode yang sedang dibuka: EvaluationPeriod::open()->first().
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', PeriodStatus::Open);
    }

    /**
     * Buka periode ini & tutup periode open lain — periode tunggal
     * (PRD §6.2/§7.7). Dipanggil dari controller/service di Fase 5.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('status', PeriodStatus::Open)
                ->whereKeyNot($this->getKey())
                ->update(['status' => PeriodStatus::Closed]);

            $this->update(['status' => PeriodStatus::Open]);
        });
    }

    /**
     * @return HasMany<CourseClassAssignment, $this>
     */
    public function courseClassAssignments(): HasMany
    {
        return $this->hasMany(CourseClassAssignment::class);
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
```

> `#[Scope]` adalah attribute local scope Laravel 12+ (`Illuminate\Database\Eloquent\Attributes\Scope`) — konsisten dengan gaya attribute project. Nama method `open` dipanggil sebagai `EvaluationPeriod::open()`. Kalau lebih suka gaya lama, ganti jadi `public function scopeOpen(Builder $query)`.

**`database/factories/EvaluationPeriodFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationPeriod>
 */
class EvaluationPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Ganjil 2025/2026',
            'academic_year' => '2025/2026',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => PeriodStatus::Draft,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PeriodStatus::Open]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PeriodStatus::Closed]);
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/SemesterType.php app/Enums/PeriodStatus.php app/Models/EvaluationPeriod.php database/factories/EvaluationPeriodFactory.php
git commit -m "Fase 2: model EvaluationPeriod + enum SemesterType & PeriodStatus

Cast tanggal & enum; scope open(); method activate() menegakkan
periode tunggal dalam transaction (PRD §6.2/§7.7, dipakai Fase 5).
hasMany courseClassAssignments & evaluations (PRD §4.8).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 8 — EvaluationQuestion

```bash
php artisan make:model EvaluationQuestion --factory --no-interaction
```

**`app/Models/EvaluationQuestion.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\EvaluationQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category', 'question_text', 'order_number', 'is_active'])]
class EvaluationQuestion extends Model
{
    /** @use HasFactory<EvaluationQuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Pertanyaan aktif terurut: EvaluationQuestion::active()->get().
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('order_number');
    }

    /**
     * @return HasMany<EvaluationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }
}
```

**`database/factories/EvaluationQuestionFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationQuestion>
 */
class EvaluationQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement([
                'Penguasaan & Penyampaian Materi',
                'Interaksi & Ketersediaan',
                'Kedisiplinan & Profesionalisme',
            ]),
            'question_text' => 'Bagaimana penilaian Anda terhadap '.fake()->words(3, true).'?',
            'order_number' => fake()->unique()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/EvaluationQuestion.php database/factories/EvaluationQuestionFactory.php
git commit -m "Fase 2: model EvaluationQuestion

Scope active() (is_active + urut order_number); hasMany answers
(PRD §4.9, §5). category disimpan sebagai string.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 9 — CourseClassAssignment

```bash
php artisan make:model CourseClassAssignment --factory --no-interaction
```

**`app/Models/CourseClassAssignment.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\CourseClassAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'lecturer_id', 'class_group_id', 'evaluation_period_id', 'created_by'])]
class CourseClassAssignment extends Model
{
    /** @use HasFactory<CourseClassAssignmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Lecturer, $this>
     */
    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    /**
     * @return BelongsTo<ClassGroup, $this>
     */
    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /**
     * @return BelongsTo<EvaluationPeriod, $this>
     */
    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
```

**`database/factories/CourseClassAssignmentFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseClassAssignment>
 */
class CourseClassAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'lecturer_id' => Lecturer::factory(),
            'class_group_id' => ClassGroup::factory(),
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/CourseClassAssignment.php database/factories/CourseClassAssignmentFactory.php
git commit -m "Fase 2: model CourseClassAssignment

Inti team teaching (PRD §4.4): belongsTo course/lecturer/classGroup/
evaluationPeriod/creator; hasMany evaluations. Evaluasi dilakukan per
baris assignment (per pasangan dosen-MK).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 10 — Evaluation

```bash
php artisan make:model Evaluation --factory --no-interaction
```

**`app/Models/Evaluation.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['student_id', 'course_class_assignment_id', 'evaluation_period_id', 'submitted_at'])]
class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * student_id JANGAN diekspos ke endpoint dosen/kaprodi (anonimitas
     * PRD §7, §8). Relasi ini hanya untuk validasi internal.
     *
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<CourseClassAssignment, $this>
     */
    public function courseClassAssignment(): BelongsTo
    {
        return $this->belongsTo(CourseClassAssignment::class);
    }

    /**
     * @return BelongsTo<EvaluationPeriod, $this>
     */
    public function evaluationPeriod(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class);
    }

    /**
     * @return HasMany<EvaluationAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(EvaluationAnswer::class);
    }

    /**
     * @return HasOne<EvaluationImpression, $this>
     */
    public function impression(): HasOne
    {
        return $this->hasOne(EvaluationImpression::class);
    }
}
```

**`database/factories/EvaluationFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_class_assignment_id' => CourseClassAssignment::factory(),
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'submitted_at' => now(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Evaluation.php database/factories/EvaluationFactory.php
git commit -m "Fase 2: model Evaluation

belongsTo student/courseClassAssignment/evaluationPeriod; hasMany
answers; hasOne impression (PRD §4.10). student_id tidak diekspos ke
endpoint dosen (anonimitas §7/§8).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 11 — EvaluationAnswer

```bash
php artisan make:model EvaluationAnswer --factory --no-interaction
```

**`app/Models/EvaluationAnswer.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\EvaluationAnswerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'evaluation_question_id', 'star_rating'])]
class EvaluationAnswer extends Model
{
    /** @use HasFactory<EvaluationAnswerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'star_rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    /**
     * @return BelongsTo<EvaluationQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(EvaluationQuestion::class, 'evaluation_question_id');
    }
}
```

**`database/factories/EvaluationAnswerFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationAnswer>
 */
class EvaluationAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'evaluation_question_id' => EvaluationQuestion::factory(),
            'star_rating' => fake()->numberBetween(1, 5),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/EvaluationAnswer.php database/factories/EvaluationAnswerFactory.php
git commit -m "Fase 2: model EvaluationAnswer

belongsTo evaluation & question; cast star_rating (PRD §4.11). Relasi
question memakai FK eksplisit evaluation_question_id.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 12 — EvaluationImpression

```bash
php artisan make:model EvaluationImpression --factory --no-interaction
```

**`app/Models/EvaluationImpression.php`**:

```php
<?php

namespace App\Models;

use Database\Factories\EvaluationImpressionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['evaluation_id', 'impression_text', 'suggestion_text'])]
class EvaluationImpression extends Model
{
    /** @use HasFactory<EvaluationImpressionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Evaluation, $this>
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }
}
```

**`database/factories/EvaluationImpressionFactory.php`**:

```php
<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationImpression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationImpression>
 */
class EvaluationImpressionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'impression_text' => fake()->sentence(),
            'suggestion_text' => fake()->sentence(),
        ];
    }
}
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/EvaluationImpression.php database/factories/EvaluationImpressionFactory.php
git commit -m "Fase 2: model EvaluationImpression

belongsTo evaluation; kesan & saran anonim, 1:1 dengan evaluation
(PRD §4.12). Model terakhir Fase 2.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 13 — Unit test relasi + update TODO.md

Verifikasi menyeluruh: bikin tiap model via factory & pastikan relasi tidak error.

```bash
php artisan make:test ModelRelationshipTest --pest --no-interaction
```

**`tests/Feature/ModelRelationshipTest.php`** (ganti seluruh isi):

```php
<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\Student;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('membuat seluruh graf model via factory tanpa error', function () {
    $answer = EvaluationAnswer::factory()->create();
    $impression = EvaluationImpression::factory()->create();

    expect($answer->exists)->toBeTrue();
    expect($impression->exists)->toBeTrue();
});

it('menghubungkan relasi student sampai ke prodi & kelas', function () {
    $student = Student::factory()->create();

    expect($student->user)->not->toBeNull();
    expect($student->studyProgram)->not->toBeNull();
    expect($student->classGroup)->not->toBeNull();
    expect($student->creator->isAdmin())->toBeTrue();
});

it('menghubungkan evaluation ke answers & impression', function () {
    $evaluation = Evaluation::factory()
        ->has(EvaluationAnswer::factory()->count(3), 'answers')
        ->has(EvaluationImpression::factory(), 'impression')
        ->create();

    expect($evaluation->answers)->toHaveCount(3);
    expect($evaluation->impression)->not->toBeNull();
    expect($evaluation->student)->not->toBeNull();
});

it('menghubungkan assignment ke course, lecturer, class, period', function () {
    $assignment = CourseClassAssignment::factory()->create();

    expect($assignment->course)->not->toBeNull();
    expect($assignment->lecturer->user->isLecturer())->toBeTrue();
    expect($assignment->classGroup)->not->toBeNull();
    expect($assignment->evaluationPeriod)->not->toBeNull();
    expect($assignment->creator->isAdmin())->toBeTrue();
});

it('menegakkan periode evaluasi tunggal saat activate()', function () {
    $lama = App\Models\EvaluationPeriod::factory()->open()->create();
    $baru = App\Models\EvaluationPeriod::factory()->create();

    $baru->activate();

    expect($baru->fresh()->status)->toBe(App\Enums\PeriodStatus::Open);
    expect($lama->fresh()->status)->toBe(App\Enums\PeriodStatus::Closed);
});
```

Jalankan test (harus hijau semua):

```bash
php artisan test --compact --filter=ModelRelationshipTest
```

Lalu **update TODO.md** — centang semua item Fase 2 jadi `[x]` dan update baris **Status project saat ini** jadi kira-kira:

> *Fase 0, 1 & 2 selesai. Fase 2: 12 model + relasi Eloquent, 5 PHP enum (`Role`, `StudentStatus`, `DegreeLevel`, `SemesterType`, `PeriodStatus`) di `app/Enums/`, factory tiap model, unit test relasi hijau. Siap lanjut Fase 3 (Seeder & Factory data dummy).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/ModelRelationshipTest.php TODO.md
git commit -m "Fase 2: unit test relasi model + update checklist TODO.md

Test smoke seluruh graf model via factory (Student->prodi/kelas,
Evaluation->answers/impression, assignment->4 relasi), plus verifikasi
EvaluationPeriod::activate() menegakkan periode tunggal. Semua hijau.
Fase 2 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Checklist hal yang mudah terlewat

- [ ] **`role` wajib di UserFactory** — kolomnya NOT NULL tanpa default; kalau lupa, semua factory yang bikin user gagal.
- [ ] **`creator()` pakai FK eksplisit** `'created_by'` (di `Student` & `CourseClassAssignment`) — kalau tidak, Eloquent cari kolom `user_id`.
- [ ] **`question()` di EvaluationAnswer** pakai FK eksplisit `'evaluation_question_id'`.
- [ ] **`#[Scope]`** butuh import `Illuminate\Database\Eloquent\Attributes\Scope` + `Illuminate\Database\Eloquent\Builder`. Kalau ragu, pakai `public function scopeOpen(Builder $query): void` klasik.
- [ ] Jalankan **`vendor/bin/pint --dirty --format agent`** sebelum tiap commit (wajib per CLAUDE.md).
- [ ] Push ke GitHub setelah selesai: `git push origin master`.

> File panduan ini boleh dihapus setelah Fase 2 kelar (opsional): `git rm PANDUAN_FASE_2.md`.
