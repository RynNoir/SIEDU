<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Services\AssignmentResultService;
use App\Services\CsvExportService;
use App\Services\EvaluationExportService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = auth()->user()->lecturer;

        // Opsi dropdown dibatasi ke MK/kelas yang benar-benar pernah diampu dosen ini.
        $ownAssignments = CourseClassAssignment::where('lecturer_id', $lecturer->id);

        return view('lecturer.dashboard', [
            'assignments' => $this->filteredAssignments($request),
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'courses' => Course::whereIn('id', (clone $ownAssignments)->distinct()->pluck('course_id'))->orderBy('name')->get(),
            'classGroups' => ClassGroup::whereIn('id', (clone $ownAssignments)->distinct()->pluck('class_group_id'))->orderBy('class_code')->get(),
            'selectedPeriodId' => $request->input('period_id'),
            'selectedCourseId' => $request->input('course_id'),
            'selectedClassGroupId' => $request->input('class_group_id'),
        ]);
    }

    /**
     * Unduh rekap kelas yang diampu, sesuai filter yang sedang aktif.
     */
    public function export(Request $request, EvaluationExportService $export, CsvExportService $csv): StreamedResponse
    {
        $rows = $export->recapRows($this->filteredAssignments($request));

        return $csv->download(
            $csv->filename('rekap-evaluasi', auth()->user()->lecturer->name),
            $rows,
        );
    }

    public function show(Request $request, CourseClassAssignment $assignment, AssignmentResultService $results): View
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'classGroup', 'evaluationPeriod', 'lecturer']);

        return view('lecturer.assignments.show', [
            'assignment' => $assignment,
            'ratingFilter' => $request->input('rating'),
            ...$results->for($assignment, $request->input('rating')),
        ]);
    }

    /**
     * Unduh detail satu penugasan: skor per kategori + kesan & saran anonim.
     */
    public function exportAssignment(CourseClassAssignment $assignment, EvaluationExportService $export, CsvExportService $csv): StreamedResponse
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'classGroup', 'evaluationPeriod', 'lecturer']);

        return $csv->download(
            $csv->filename('hasil-evaluasi', $assignment->course->code.'-'.$assignment->classGroup->class_code),
            $export->detailRows($assignment),
        );
    }

    /**
     * Dipakai bersama tampilan & ekspor supaya filter keduanya tidak bisa berbeda.
     * Selalu dibatasi ke penugasan dosen yang sedang masuk.
     *
     * @return Collection<int, CourseClassAssignment>
     */
    private function filteredAssignments(Request $request): Collection
    {
        return CourseClassAssignment::query()
            ->with([
                'course', 'evaluationPeriod', 'lecturer',
                'classGroup' => fn ($q) => $q->withCount('students'),
            ])
            ->where('lecturer_id', auth()->user()->lecturer->id)
            ->when($request->input('period_id'), fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->when($request->input('course_id'), fn ($q, $id) => $q->where('course_id', $id))
            ->when($request->input('class_group_id'), fn ($q, $id) => $q->where('class_group_id', $id))
            ->withCount('evaluations')
            ->orderByDesc('evaluation_period_id')
            ->get();
    }
}
