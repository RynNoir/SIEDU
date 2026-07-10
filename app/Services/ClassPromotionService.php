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
