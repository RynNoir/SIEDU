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
use Illuminate\View\View;

class CourseClassAssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = CourseClassAssignment::with(['course', 'lecturer', 'classGroup', 'evaluationPeriod'])
            ->latest('id')
            ->paginate(20);

        return view('admin.course-class-assignments.index', compact('assignments'));
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
