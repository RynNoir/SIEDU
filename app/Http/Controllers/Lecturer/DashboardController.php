<?php

namespace App\Http\Controllers\Lecturer;

use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
}
