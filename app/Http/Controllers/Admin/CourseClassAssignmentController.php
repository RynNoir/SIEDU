<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseClassAssignmentRequest;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseClassAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $assignments = CourseClassAssignment::query()
            ->with(['course', 'lecturer', 'classGroup', 'evaluationPeriod'])
            ->when($request->input('course_id'), fn ($q, $id) => $q->where('course_id', $id))
            ->when($request->input('lecturer_id'), fn ($q, $id) => $q->where('lecturer_id', $id))
            ->when($request->input('class_group_id'), fn ($q, $id) => $q->where('class_group_id', $id))
            ->when($request->input('evaluation_period_id'), fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.course-class-assignments.index', [
            'assignments' => $assignments,
            ...$this->formData(),
        ]);
    }

    public function create(): View
    {
        return view('admin.course-class-assignments.create', $this->formData());
    }

    public function store(CourseClassAssignmentRequest $request): RedirectResponse
    {
        CourseClassAssignment::create([...$request->validated(), 'created_by' => auth()->id()]);

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan dosen ditambahkan.');
    }

    public function edit(CourseClassAssignment $courseClassAssignment): View
    {
        return view('admin.course-class-assignments.edit', [
            'assignment' => $courseClassAssignment,
            ...$this->formData(),
        ]);
    }

    public function update(CourseClassAssignmentRequest $request, CourseClassAssignment $courseClassAssignment): RedirectResponse
    {
        $courseClassAssignment->update($request->validated());

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan diperbarui.');
    }

    public function destroy(CourseClassAssignment $courseClassAssignment): RedirectResponse
    {
        $courseClassAssignment->delete();

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'courses' => Course::with('studyProgram')->orderBy('code')->get(),
            'lecturers' => Lecturer::orderBy('name')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'periods' => EvaluationPeriod::orderBy('start_date', 'desc')->get(),
        ];
    }
}
