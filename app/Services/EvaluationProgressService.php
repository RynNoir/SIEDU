<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Progres pengisian evaluasi per kelas pada periode yang sedang dibuka.
 *
 * Menjawab satu pertanyaan operasional admin selama periode berjalan: **kelas
 * mana yang perlu diingatkan?** Dashboard sebelumnya hanya menampilkan total
 * masuk dan grafik harian — berguna untuk melihat tren, tapi tak bisa
 * menunjukkan bahwa MI2B baru terisi 3 dari 25 sementara kelas lain hampir penuh.
 *
 * Dua angka dilaporkan berdampingan karena masing-masing bisa menyesatkan bila
 * berdiri sendiri:
 *
 * - **Selesai**: mahasiswa yang sudah mengisi SELURUH penugasan kelasnya. Ini
 *   yang dipakai untuk menagih, karena mahasiswa yang baru mengisi 4 dari 5
 *   tetap perlu diingatkan.
 * - **Progres**: evaluasi terkumpul dibanding total yang diharapkan. Tanpa ini,
 *   kelas yang semua anggotanya kurang satu evaluasi akan terbaca "0 selesai"
 *   dan terlihat seperti belum tersentuh sama sekali.
 *
 * Seluruh angka bersifat agregat per kelas — tidak ada identitas mahasiswa,
 * sesuai aturan anonimitas PRD §8.
 */
class EvaluationProgressService
{
    /**
     * @return list<array{
     *     class_code:string, study_program:string, assignments:int, students:int,
     *     completed:int, submitted:int, expected:int, percentage:float
     * }>
     */
    public function forPeriod(?EvaluationPeriod $period): array
    {
        if (! $period) {
            return [];
        }

        $assignmentsByClass = CourseClassAssignment::query()
            ->where('evaluation_period_id', $period->id)
            ->selectRaw('class_group_id, COUNT(*) as total')
            ->groupBy('class_group_id')
            ->pluck('total', 'class_group_id');

        if ($assignmentsByClass->isEmpty()) {
            return [];
        }

        $classes = ClassGroup::with('studyProgram')
            ->whereIn('id', $assignmentsByClass->keys())
            ->orderBy('class_code')
            ->get();

        $studentsByClass = Student::query()
            ->where('status', StudentStatus::Aktif)
            ->whereIn('class_group_id', $assignmentsByClass->keys())
            ->selectRaw('class_group_id, COUNT(*) as total')
            ->groupBy('class_group_id')
            ->pluck('total', 'class_group_id');

        $submittedByClass = $this->submittedByClass($period);
        $completedByClass = $this->completedByClass($period, $assignmentsByClass->all());

        $rows = [];

        foreach ($classes as $class) {
            $assignments = (int) $assignmentsByClass[$class->id];
            $students = (int) ($studentsByClass[$class->id] ?? 0);
            $expected = $students * $assignments;
            $submitted = (int) ($submittedByClass[$class->id] ?? 0);

            $rows[] = [
                'class_code' => $class->class_code,
                'study_program' => $class->studyProgram->code,
                'assignments' => $assignments,
                'students' => $students,
                'completed' => (int) ($completedByClass[$class->id] ?? 0),
                'submitted' => $submitted,
                'expected' => $expected,
                'percentage' => $expected > 0 ? round($submitted / $expected * 100, 1) : 0.0,
            ];
        }

        // Terendah dulu — inilah urutan yang berguna saat admin menagih.
        usort($rows, fn (array $a, array $b): int => $a['percentage'] <=> $b['percentage']);

        return $rows;
    }

    /**
     * Jumlah evaluasi yang sudah masuk per kelas.
     *
     * @return Collection<int, int>
     */
    private function submittedByClass(EvaluationPeriod $period): Collection
    {
        return Evaluation::query()
            ->join('course_class_assignments as a', 'evaluations.course_class_assignment_id', '=', 'a.id')
            ->where('evaluations.evaluation_period_id', $period->id)
            ->groupBy('a.class_group_id')
            ->selectRaw('a.class_group_id, COUNT(*) as total')
            ->pluck('total', 'class_group_id');
    }

    /**
     * Jumlah mahasiswa per kelas yang sudah menuntaskan seluruh penugasannya.
     *
     * Dihitung dari jumlah evaluasi per mahasiswa, lalu dibandingkan dengan
     * jumlah penugasan kelasnya di PHP — perbandingan itu tak bisa dinyatakan
     * di satu GROUP BY karena ambangnya berbeda tiap kelas.
     *
     * @param  array<int, int>  $assignmentsByClass
     * @return array<int, int>
     */
    private function completedByClass(EvaluationPeriod $period, array $assignmentsByClass): array
    {
        $perStudent = Evaluation::query()
            ->join('students as s', 'evaluations.student_id', '=', 's.id')
            ->where('evaluations.evaluation_period_id', $period->id)
            ->groupBy('s.class_group_id', 'evaluations.student_id')
            ->selectRaw('s.class_group_id, evaluations.student_id, COUNT(*) as total')
            ->get();

        $completed = [];

        foreach ($perStudent as $row) {
            $required = $assignmentsByClass[$row->class_group_id] ?? 0;

            if ($required > 0 && $row->total >= $required) {
                $completed[$row->class_group_id] = ($completed[$row->class_group_id] ?? 0) + 1;
            }
        }

        return $completed;
    }
}
