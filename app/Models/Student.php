<?php

namespace App\Models;

use App\Enums\StudentStatus;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id', 'nim', 'name', 'study_program_id', 'enrollment_year',
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
            'enrollment_year' => 'integer',
            'current_semester' => 'integer',
            'status' => StudentStatus::class,
        ];
    }

    /**
     * Mahasiswa yang masih terdaftar — semua kecuali yang sudah lulus.
     *
     * Cuti & DO tetap termasuk: admin masih perlu menemukan mahasiswa cuti untuk
     * mengaktifkannya kembali, dan DO disimpan untuk histori. Yang dipisahkan
     * hanya alumni, karena jumlahnya menumpuk tiap tahun dan kelas terakhirnya
     * membuat daftar kelas di halaman mahasiswa jadi rancu.
     */
    #[Scope]
    protected function enrolled(Builder $query): void
    {
        $query->where('status', '!=', StudentStatus::Lulus);
    }

    /**
     * Alumni: mahasiswa yang sudah menuntaskan studinya.
     */
    #[Scope]
    protected function alumni(Builder $query): void
    {
        $query->where('status', StudentStatus::Lulus);
    }

    /**
     * Turunkan tahun angkatan dari kelas + semester yang sedang ditempuh.
     *
     * Hanya dipakai sebagai nilai default/backfill — begitu tersimpan, angkatan
     * tidak pernah dihitung ulang (mahasiswa yang cuti akan tertinggal dari
     * kelasnya, dan angkatan aslinya harus tetap utuh).
     */
    public static function deriveEnrollmentYear(string $academicYear, int $currentSemester): int
    {
        return (int) substr($academicYear, 0, 4) - ((int) ceil($currentSemester / 2) - 1);
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
