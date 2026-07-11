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
        ]);
    }
}
