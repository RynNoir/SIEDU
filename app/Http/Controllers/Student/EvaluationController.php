<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->first();

        $assignments = collect();
        $doneIds = [];

        if ($period && $student) {
            $assignments = CourseClassAssignment::with(['course', 'lecturer'])
                ->where('class_group_id', $student->class_group_id)
                ->where('evaluation_period_id', $period->id)
                ->get();

            $doneIds = Evaluation::where('student_id', $student->id)
                ->where('evaluation_period_id', $period->id)
                ->pluck('course_class_assignment_id')
                ->all();
        }

        return view('student.evaluations.index', compact('period', 'assignments', 'doneIds'));
    }
}
