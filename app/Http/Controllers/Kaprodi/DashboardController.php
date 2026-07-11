<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $prodiId = auth()->user()->study_program_id;
        $lecturerId = $request->input('lecturer_id');
        $periodId = $request->input('period_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'lecturer', 'classGroup', 'evaluationPeriod'])
            ->whereRelation('classGroup', 'study_program_id', $prodiId)
            ->when($lecturerId, fn ($q, $id) => $q->where('lecturer_id', $id))
            ->when($periodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->withCount('evaluations')
            ->get();

        // Rata-rata per assignment dalam satu query (cegah N+1).
        $avgById = EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->whereIn('evaluations.course_class_assignment_id', $assignments->pluck('id'))
            ->groupBy('evaluations.course_class_assignment_id')
            ->selectRaw('evaluations.course_class_assignment_id as assignment_id, AVG(evaluation_answers.star_rating) as avg_rating')
            ->pluck('avg_rating', 'assignment_id');

        return view('kaprodi.dashboard', [
            'byCourse' => $assignments->groupBy('course_id'),
            'avgById' => $avgById,
            'lecturers' => Lecturer::where('study_program_id', $prodiId)->orderBy('name')->get(),
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'lecturerId' => $lecturerId,
            'periodId' => $periodId,
        ]);
    }
}
