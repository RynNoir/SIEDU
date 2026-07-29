<?php

namespace App\Services;

use App\Enums\SemesterType;
use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SemesterPromotionService
{
    /**
     * Naikkan mahasiswa satu semester (PRD §6.1).
     *
     * Perkuliahan berjalan per periode ganjil/genap, jadi promosi selalu +1
     * semester — bukan +2 sekali setahun:
     *
     * - Dari GANJIL -> genap: semester +1, kelas & tahun ajaran TIDAK berubah.
     * - Dari GENAP  -> ganjil: semester +1, naik satu tingkat kelas sekaligus
     *                  pindah ke tahun ajaran berikutnya.
     *
     * Periode tujuan tidak diminta dari pemanggil karena sepenuhnya bisa
     * diturunkan dari periode asal.
     *
     * @return array{
     *     students_promoted:int, students_graduated:int, classes_created:int,
     *     to_academic_year:string, to_semester_type:SemesterType
     * }
     */
    public function promote(string $academicYear, SemesterType $semesterType): array
    {
        return DB::transaction(function () use ($academicYear, $semesterType): array {
            $toAcademicYear = $this->nextAcademicYear($academicYear, $semesterType);

            $summary = [
                'students_promoted' => 0,
                'students_graduated' => 0,
                'classes_created' => 0,
                'to_academic_year' => $toAcademicYear,
                'to_semester_type' => $this->nextSemesterType($semesterType),
            ];

            foreach ($this->sourceClasses($academicYear) as $class) {
                $totalSemesters = $class->studyProgram->total_semesters;

                foreach ($this->promotableStudents($class, $semesterType) as $student) {
                    // Sudah menuntaskan semester terakhir prodinya — tidak dinaikkan lagi.
                    if ($student->current_semester >= $totalSemesters) {
                        $summary['students_graduated']++;

                        continue;
                    }

                    $attributes = ['current_semester' => $student->current_semester + 1];

                    // Hanya pergantian genap -> ganjil yang memindahkan kelas;
                    // ganjil -> genap tetap di kelas yang sama.
                    if ($semesterType === SemesterType::Genap) {
                        $attributes['class_group_id'] = $this->nextLevelClass($class, $toAcademicYear, $summary)->id;
                    }

                    $student->update($attributes);
                    $summary['students_promoted']++;
                }
            }

            return $summary;
        });
    }

    /**
     * Kelas sumber promosi. Satu kelas membentang dua semester dalam satu tahun
     * ajaran (MI1A 2025/2026 = semester 1 dan 2), jadi tahun ajaran saja sudah
     * cukup untuk menentukan kelas asal — tidak ada kolom semester di kelas.
     *
     * @return Collection<int, ClassGroup>
     */
    private function sourceClasses(string $academicYear): Collection
    {
        return ClassGroup::with('studyProgram')
            ->where('academic_year', $academicYear)
            ->get();
    }

    /**
     * Mahasiswa yang boleh dinaikkan pada periode ini.
     *
     * Filter paritas semester adalah kunci idempotensi: promosi ganjil -> genap
     * tidak memindahkan kelas, jadi tanpa filter ini menekan tombol dua kali
     * akan menaikkan semester +2. Dengan hanya mengambil mahasiswa yang paritas
     * semesternya cocok dengan periode asal (ganjil = semester ganjil), proses
     * kedua tidak menemukan siapa pun.
     *
     * Efek sampingnya menguntungkan: keputusan diambil dari semester tiap
     * mahasiswa, bukan dari tingkat kelasnya, sehingga mahasiswa yang tertinggal
     * setelah cuti ikut tertangani benar.
     *
     * Mahasiswa cuti, DO, dan yang sudah lulus tidak pernah ikut (PRD §6.1).
     *
     * @return Collection<int, Student>
     */
    private function promotableStudents(ClassGroup $class, SemesterType $semesterType): Collection
    {
        $expectedParity = $semesterType === SemesterType::Ganjil ? 1 : 0;

        return $class->students()
            ->where('status', StudentStatus::Aktif)
            ->get()
            ->filter(fn (Student $student): bool => $student->current_semester % 2 === $expectedParity)
            ->values();
    }

    /**
     * Buat/temukan kelas satu tingkat di atas pada tahun ajaran berikutnya.
     * firstOrCreate menjaga proses tetap idempotent bila dijalankan ulang.
     *
     * @param  array{classes_created:int, ...}  $summary
     */
    private function nextLevelClass(ClassGroup $class, string $toAcademicYear, array &$summary): ClassGroup
    {
        $newYearLevel = $class->year_level + 1;

        $newClass = ClassGroup::firstOrCreate(
            [
                'academic_year' => $toAcademicYear,
                'class_code' => $class->studyProgram->code.$newYearLevel.$class->class_letter,
            ],
            [
                'study_program_id' => $class->study_program_id,
                'year_level' => $newYearLevel,
                'class_letter' => $class->class_letter,
                'capacity' => $class->capacity,
            ],
        );

        if ($newClass->wasRecentlyCreated) {
            $summary['classes_created']++;
        }

        return $newClass;
    }

    /**
     * Ganjil -> genap tetap di tahun ajaran yang sama; genap -> ganjil pindah ke
     * tahun ajaran berikutnya (2025/2026 -> 2026/2027).
     */
    public function nextAcademicYear(string $academicYear, SemesterType $semesterType): string
    {
        if ($semesterType === SemesterType::Ganjil) {
            return $academicYear;
        }

        $startYear = (int) substr($academicYear, 0, 4);

        return ($startYear + 1).'/'.($startYear + 2);
    }

    public function nextSemesterType(SemesterType $semesterType): SemesterType
    {
        return $semesterType === SemesterType::Ganjil ? SemesterType::Genap : SemesterType::Ganjil;
    }
}
