<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassGroupRequest;
use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassGroupController extends Controller
{
    public function index(): View
    {
        $classGroups = ClassGroup::with('studyProgram')
            ->orderBy('academic_year', 'desc')
            ->orderBy('class_code')
            ->paginate(20);

        return view('admin.class-groups.index', compact('classGroups'));
    }

    public function create(): View
    {
        return view('admin.class-groups.create', ['studyPrograms' => StudyProgram::orderBy('code')->get()]);
    }

    public function store(ClassGroupRequest $request): RedirectResponse
    {
        ClassGroup::create($this->withClassCode($request->validated()));

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas ditambahkan.');
    }

    public function edit(ClassGroup $classGroup): View
    {
        return view('admin.class-groups.edit', [
            'classGroup' => $classGroup,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
    }

    public function update(ClassGroupRequest $request, ClassGroup $classGroup): RedirectResponse
    {
        $classGroup->update($this->withClassCode($request->validated()));

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas dibarui.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $classGroup->delete();

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas dihapus.');
    }

    /**
     * Susun class_code = {KODE_PRODI}{TAHUN}{HURUF} (PRD §2.2).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withClassCode(array $data): array
    {
        $prodi = StudyProgram::findOrFail($data['study_program_id']);
        $data['class_letter'] = strtoupper($data['class_letter']);
        $data['class_code'] = $prodi->code.$data['year_level'].$data['class_letter'];

        return $data;
    }
}
