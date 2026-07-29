<?php

namespace App\Services;

use App\Enums\SemesterType;
use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\EvaluationPeriod;
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

            foreach ($this->buildPlan($academicYear, $semesterType) as $group) {
                // Sudah menuntaskan semester terakhir prodinya -> lulus.
                // class_group_id & current_semester sengaja dibiarkan di nilai
                // terakhir sebagai histori; angkatan (enrollment_year) yang jadi
                // penanda kohort alumni. Status Lulus juga otomatis mengeluarkan
                // mahasiswa dari promosi berikutnya (hanya Aktif yang ikut).
                if ($group['graduates']) {
                    foreach ($group['students'] as $student) {
                        $student->update(['status' => StudentStatus::Lulus]);
                        $summary['students_graduated']++;
                    }

                    continue;
                }

                $attributes = ['current_semester' => $group['to_semester']];

                // Hanya pergantian genap -> ganjil yang memindahkan kelas;
                // ganjil -> genap tetap di kelas yang sama.
                if ($semesterType === SemesterType::Genap) {
                    $attributes['class_group_id'] = $this->nextLevelClass($group['class'], $toAcademicYear, $summary)->id;
                }

                foreach ($group['students'] as $student) {
                    $student->update($attributes);
                    $summary['students_promoted']++;
                }
            }

            return $summary;
        });
    }

    /**
     * Pratinjau promosi tanpa mengubah apa pun (dry-run).
     *
     * Dipakai supaya admin bisa melihat "siapa pindah ke mana" sebelum
     * mengeksekusi operasi massal ini. Memakai rencana yang sama persis dengan
     * promote(), jadi tidak mungkin melenceng dari yang benar-benar terjadi.
     *
     * @return array{
     *     rows:list<array{
     *         from_class:string, to_class:string, from_semester:int,
     *         to_semester:int|null, students:int, graduates:bool
     *     }>,
     *     students_promoted:int, students_graduated:int,
     *     to_academic_year:string, to_semester_type:SemesterType
     * }
     */
    public function preview(string $academicYear, SemesterType $semesterType): array
    {
        $preview = [
            'rows' => [],
            'students_promoted' => 0,
            'students_graduated' => 0,
            'to_academic_year' => $this->nextAcademicYear($academicYear, $semesterType),
            'to_semester_type' => $this->nextSemesterType($semesterType),
        ];

        foreach ($this->buildPlan($academicYear, $semesterType) as $group) {
            $count = $group['students']->count();

            $preview['rows'][] = [
                'from_class' => $group['class']->class_code,
                // Kelas tujuan dihitung tanpa dibuat — pratinjau tak boleh menulis apa pun.
                'to_class' => $group['graduates'] ? '—' : ($semesterType === SemesterType::Genap
                    ? $this->nextClassCode($group['class'])
                    : $group['class']->class_code),
                'from_semester' => $group['from_semester'],
                'to_semester' => $group['graduates'] ? null : $group['to_semester'],
                'students' => $count,
                'graduates' => $group['graduates'],
            ];

            $preview[$group['graduates'] ? 'students_graduated' : 'students_promoted'] += $count;
        }

        return $preview;
    }

    /**
     * Rencana promosi: mahasiswa yang memenuhi syarat, dikelompokkan per kelas +
     * semester asal. Satu kelas bisa menghasilkan lebih dari satu kelompok kalau
     * ada mahasiswa yang tertinggal setelah cuti.
     *
     * Dipakai bersama oleh promote() dan preview() supaya pratinjau tidak bisa
     * berbeda dari eksekusi sungguhan.
     *
     * @return list<array{
     *     class:ClassGroup, students:Collection<int, Student>,
     *     from_semester:int, to_semester:int, graduates:bool
     * }>
     */
    private function buildPlan(string $academicYear, SemesterType $semesterType): array
    {
        $plan = [];

        foreach ($this->sourceClasses($academicYear) as $class) {
            $totalSemesters = $class->studyProgram->total_semesters;

            $groups = $this->promotableStudents($class, $semesterType)
                ->groupBy('current_semester')
                ->sortKeys();

            foreach ($groups as $fromSemester => $students) {
                $plan[] = [
                    'class' => $class,
                    'students' => $students,
                    'from_semester' => (int) $fromSemester,
                    'to_semester' => (int) $fromSemester + 1,
                    'graduates' => (int) $fromSemester >= $totalSemesters,
                ];
            }
        }

        return $plan;
    }

    /**
     * Periode evaluasi yang masih dibuka dan karenanya memblokir promosi.
     *
     * Menaikkan semester saat periode masih open akan memindahkan mahasiswa ke
     * kelas baru, sehingga daftar evaluasinya langsung berganti mengikuti kelas
     * tersebut dan evaluasi yang belum sempat diisi tak bisa lagi diakses —
     * padahal periodenya masih berjalan. Admin harus menutup periodenya dulu.
     */
    public function blockingPeriod(): ?EvaluationPeriod
    {
        return EvaluationPeriod::open()->first();
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
     * Kode kelas satu tingkat di atas, tanpa membuat kelasnya. Dipakai pratinjau,
     * yang harus benar-benar read-only.
     */
    private function nextClassCode(ClassGroup $class): string
    {
        return $class->studyProgram->code.($class->year_level + 1).$class->class_letter;
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
