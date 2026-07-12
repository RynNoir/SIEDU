<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $openPeriod = EvaluationPeriod::open()->first();

        return view('admin.dashboard', [
            'studyProgramCount' => StudyProgram::count(),
            'lecturerCount' => Lecturer::count(),
            'activeStudentCount' => Student::where('status', StudentStatus::Aktif)->count(),
            'classGroupCount' => ClassGroup::count(),
            'courseCount' => Course::count(),
            'assignmentCount' => CourseClassAssignment::count(),
            'openPeriod' => $openPeriod,
            'openPeriodEvaluationCount' => $openPeriod ? $openPeriod->evaluations()->count() : 0,
            'dailyEvaluationLabels' => $this->dailyEvaluationLabels(),
            'dailyEvaluationCounts' => $this->dailyEvaluationCounts($openPeriod),
            'studentStatusSegments' => $this->studentStatusSegments(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function dailyEvaluationLabels(): array
    {
        return collect(range(13, 0))
            ->map(fn (int $i) => now()->subDays($i)->translatedFormat('d/m'))
            ->all();
    }

    /**
     * Jumlah evaluasi masuk per hari, 14 hari terakhir (agregat, tanpa identitas mahasiswa).
     *
     * @return array<int, int>
     */
    private function dailyEvaluationCounts(?EvaluationPeriod $period): array
    {
        if (! $period) {
            return array_fill(0, 14, 0);
        }

        $counts = $period->evaluations()
            ->selectRaw('DATE(submitted_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(13, 0))
            ->map(fn (int $i) => (int) ($counts[now()->subDays($i)->toDateString()] ?? 0))
            ->all();
    }

    /**
     * Distribusi jumlah mahasiswa per status (aktif/cuti/DO/lulus) — agregat, tak sensitif.
     *
     * @return array<int, array{label: string, value: int, color: string}>
     */
    private function studentStatusSegments(): array
    {
        $labels = [
            StudentStatus::Aktif->value => 'Aktif',
            StudentStatus::Cuti->value => 'Cuti',
            StudentStatus::DO->value => 'DO',
            StudentStatus::Lulus->value => 'Lulus',
        ];

        $colors = [
            StudentStatus::Aktif->value => 'var(--color-success)',
            StudentStatus::Cuti->value => 'var(--color-warning)',
            StudentStatus::DO->value => 'var(--color-danger)',
            StudentStatus::Lulus->value => 'var(--color-muted)',
        ];

        $counts = Student::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return $counts->map(fn ($total, $status) => [
            'label' => $labels[$status] ?? (string) $status,
            'value' => (int) $total,
            'color' => $colors[$status] ?? 'var(--color-muted)',
        ])->values()->all();
    }
}
