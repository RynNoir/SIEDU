<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Services\AssignmentResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = auth()->user()->lecturer;
        $selectedPeriodId = $request->input('period_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'classGroup', 'evaluationPeriod'])
            ->where('lecturer_id', $lecturer->id)
            ->when($selectedPeriodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->withCount('evaluations')
            ->orderByDesc('evaluation_period_id')
            ->get();

        return view('lecturer.dashboard', [
            'assignments' => $assignments,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'selectedPeriodId' => $selectedPeriodId,
        ]);
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
}
