<?php

namespace App\Services;

use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use Illuminate\Support\Collection;

/**
 * Menyusun baris CSV untuk laporan hasil evaluasi.
 *
 * Yang TIDAK ada di sini disengaja: tidak ada satu pun query yang menyentuh
 * `student_id`, `nim`, atau nama mahasiswa. Ekspor detail memakai ulang
 * AssignmentResultService — sumber kebenaran yang sama dengan tampilan layar —
 * supaya aturan anonimitas (ambang minimum responden) tidak bisa melenceng
 * antara apa yang dilihat dan apa yang diunduh.
 */
class EvaluationExportService
{
    public function __construct(
        private CsvExportService $csv,
        private AssignmentResultService $results,
    ) {}

    /**
     * Rekap satu baris per penugasan — bentuk laporan yang dibutuhkan kaprodi.
     *
     * @param  Collection<int, CourseClassAssignment>  $assignments  sudah di-load relasi & withCount('evaluations')
     * @return list<array<int, string|int>>
     */
    public function recapRows(Collection $assignments): array
    {
        $avgById = $this->averageByAssignment($assignments->pluck('id'));

        $rows = [[
            'Periode', 'Tahun Ajaran', 'Semester', 'Kode MK', 'Mata Kuliah',
            'Kelas', 'Dosen', 'Responden', 'Ukuran Kelas', 'Partisipasi (%)', 'Rata-rata Skor',
        ]];

        foreach ($assignments as $assignment) {
            // Keduanya diharapkan sudah di-load pemanggil (withCount) supaya
            // rekap ratusan baris tidak memicu dua query per baris.
            $classSize = (int) ($assignment->classGroup->students_count ?? $assignment->classGroup->students()->count());
            $respondents = (int) ($assignment->evaluations_count ?? $assignment->evaluations()->count());

            $rows[] = [
                $assignment->evaluationPeriod->name,
                $assignment->evaluationPeriod->academic_year,
                $assignment->evaluationPeriod->semester_type->value,
                $assignment->course->code,
                $assignment->course->name,
                $assignment->classGroup->class_code,
                $assignment->lecturer->name,
                $respondents,
                $classSize,
                $this->csv->decimal($classSize > 0 ? $respondents / $classSize * 100 : null, 0),
                $this->csv->decimal($avgById[$assignment->id] ?? null),
            ];
        }

        return $rows;
    }

    /**
     * Detail satu penugasan: identitas, skor per kategori, lalu kesan & saran.
     *
     * Kesan & saran hanya ikut bila jumlah responden sudah mencapai ambang
     * anonimitas — keputusan itu diambil AssignmentResultService, bukan di sini,
     * jadi persis sama dengan yang tampil di layar.
     *
     * @return list<array<int, string|int>>
     */
    public function detailRows(CourseClassAssignment $assignment): array
    {
        $result = $this->results->for($assignment);

        $rows = [
            ['Laporan Hasil Evaluasi'],
            ['Mata Kuliah', $assignment->course->code.' - '.$assignment->course->name],
            ['Kelas', $assignment->classGroup->class_code],
            ['Dosen', $assignment->lecturer->name],
            ['Periode', $assignment->evaluationPeriod->name],
            ['Responden', $result['respondents'].' dari '.$result['classSize'].' mahasiswa'],
            ['Rata-rata Keseluruhan', $this->csv->decimal($result['overallAvg'])],
            [],
            ['Skor Per Kategori'],
            ['Kategori', 'Rata-rata'],
        ];

        foreach ($result['categoryScores'] as $score) {
            $rows[] = [$score->category, $this->csv->decimal((float) $score->avg_rating)];
        }

        $rows[] = [];
        $rows[] = ['Kesan & Saran (Anonim)'];

        if ($result['respondents'] < $result['threshold']) {
            $rows[] = [
                'Belum ditampilkan — minimal '.$result['threshold'].
                ' responden diperlukan untuk menjaga anonimitas mahasiswa.',
            ];

            return $rows;
        }

        if ($result['impressions']->isEmpty()) {
            $rows[] = ['Tidak ada kesan & saran yang diisi.'];

            return $rows;
        }

        $rows[] = ['Rata-rata Skor', 'Kesan', 'Saran'];

        foreach ($result['impressions'] as $impression) {
            $rows[] = [
                $this->csv->decimal((float) $impression->avg_rating),
                $impression->impression_text ?? '',
                $impression->suggestion_text ?? '',
            ];
        }

        return $rows;
    }

    /**
     * Rata-rata skor seluruh penugasan dalam satu query (cegah N+1).
     *
     * @param  Collection<int, int>  $assignmentIds
     * @return Collection<int, float>
     */
    private function averageByAssignment(Collection $assignmentIds): Collection
    {
        return EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->whereIn('evaluations.course_class_assignment_id', $assignmentIds)
            ->groupBy('evaluations.course_class_assignment_id')
            ->selectRaw('evaluations.course_class_assignment_id as assignment_id, AVG(evaluation_answers.star_rating) as avg_rating')
            ->pluck('avg_rating', 'assignment_id');
    }
}
