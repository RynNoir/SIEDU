<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DegreeLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudyProgramRequest;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    public function index(): View
    {
        $studyPrograms = StudyProgram::orderBy('code')->paginate(15);

        return view('admin.study-programs.index', compact('studyPrograms'));
    }

    public function create(): View
    {
        return view('admin.study-programs.create');
    }

    public function store(StudyProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['total_semesters'] = DegreeLevel::from($data['degree_level'])->totalSemesters();

        StudyProgram::create($data);

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi ditambahkan.');
    }

    public function edit(StudyProgram $studyProgram): View
    {
        return view('admin.study-programs.edit', compact('studyProgram'));
    }

    public function update(StudyProgramRequest $request, StudyProgram $studyProgram): RedirectResponse
    {
        $data = $request->validated();
        $data['total_semesters'] = DegreeLevel::from($data['degree_level'])->totalSemesters();

        $studyProgram->update($data);

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi diperbarui.');
    }

    public function destroy(StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->delete();

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi dihapus.');
    }
}
