<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::query()
            ->with(['studyProgram', 'classGroup'])
            ->when($request->input('search'), function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('nim', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->input('study_program_id'), fn ($q, $id) => $q->where('study_program_id', $id))
            ->when($request->input('class_group_id'), fn ($q, $id) => $q->where('class_group_id', $id))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('nim')
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(config('evaluation.default_password')),
                'role' => Role::Student,
                'must_change_password' => true,
            ]);

            $user->student()->create([
                'nim' => $data['nim'],
                'name' => $data['name'],
                'study_program_id' => $data['study_program_id'],
                'class_group_id' => $data['class_group_id'],
                'current_semester' => $data['current_semester'],
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa ditambahkan (password default: password).');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student' => $student->load('user'),
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $student): void {
            $student->update([
                'nim' => $data['nim'],
                'name' => $data['name'],
                'study_program_id' => $data['study_program_id'],
                'class_group_id' => $data['class_group_id'],
                'current_semester' => $data['current_semester'],
                'status' => $data['status'],
            ]);

            $student->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        DB::transaction(function () use ($student): void {
            $user = $student->user;
            $student->delete();
            $user?->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa dihapus.');
    }
}
