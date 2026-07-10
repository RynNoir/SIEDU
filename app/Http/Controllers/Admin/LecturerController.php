<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LecturerRequest;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(): View
    {
        $lecturers = Lecturer::with(['user', 'studyProgram'])->orderBy('name')->paginate(20);

        return view('admin.lecturers.index', compact('lecturers'));
    }

    public function create(): View
    {
        return view('admin.lecturers.create', ['studyPrograms' => StudyProgram::orderBy('code')->get()]);
    }

    public function store(LecturerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(config('evaluation.default_password')),
                'role' => Role::Lecturer,
                'must_change_password' => true,
            ]);

            $user->lecturer()->create([
                'name' => $data['name'],
                'nip' => $data['nip'],
                'study_program_id' => $data['study_program_id'],
            ]);
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen ditambahkan (password default: password).');
    }

    public function edit(Lecturer $lecturer): View
    {
        return view('admin.lecturers.edit', [
            'lecturer' => $lecturer->load('user'),
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
    }

    public function update(LecturerRequest $request, Lecturer $lecturer): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $lecturer): void {
            $lecturer->update([
                'name' => $data['name'],
                'nip' => $data['nip'],
                'study_program_id' => $data['study_program_id'],
            ]);

            $lecturer->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen diperbarui.');
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        DB::transaction(function () use ($lecturer): void {
            $user = $lecturer->user;
            $lecturer->delete();
            $user?->delete();
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen dihapus.');
    }
}
