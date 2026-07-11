<?php

namespace App\Models;

use App\Enums\SemesterType;
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

    /**
     * Tipe semester MK ini (ganjil/genap) berdasarkan paritas `semester`.
     * Kurikulum paket hanya menawarkan MK di semester ganjil ATAU genap
     * (PRD §7.8) — dipakai untuk mencocokkan dengan `evaluation_periods.semester_type`.
     */
    public function semesterType(): SemesterType
    {
        return $this->semester % 2 === 1 ? SemesterType::Ganjil : SemesterType::Genap;
    }
}
