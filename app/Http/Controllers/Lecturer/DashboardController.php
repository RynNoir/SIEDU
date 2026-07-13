<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Course;
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
        $selectedCourseId = $request->input('course_id');
        $selectedClassGroupId = $request->input('class_group_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'classGroup', 'evaluationPeriod'])
            ->where('lecturer_id', $lecturer->id)
            ->when($selectedPeriodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->when($selectedCourseId, fn ($q, $id) => $q->where('course_id', $id))
            ->when($selectedClassGroupId, fn ($q, $id) => $q->where('class_group_id', $id))
            ->withCount('evaluations')
            ->orderByDesc('evaluation_period_id')
            ->get();

        // Opsi dropdown dibatasi ke MK/kelas yang benar-benar pernah diampu dosen ini.
        $ownAssignments = CourseClassAssignment::where('lecturer_id', $lecturer->id);

        return view('lecturer.dashboard', [
            'assignments' => $assignments,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'courses' => Course::whereIn('id', (clone $ownAssignments)->distinct()->pluck('course_id'))->orderBy('name')->get(),
            'classGroups' => ClassGroup::whereIn('id', (clone $ownAssignments)->distinct()->pluck('class_group_id'))->orderBy('class_code')->get(),
            'selectedPeriodId' => $selectedPeriodId,
            'selectedCourseId' => $selectedCourseId,
            'selectedClassGroupId' => $selectedClassGroupId,
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
