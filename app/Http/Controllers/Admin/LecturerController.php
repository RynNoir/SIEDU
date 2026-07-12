<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LecturerRequest;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(Request $request): View
    {
        $lecturers = Lecturer::query()
            ->with(['user', 'studyProgram'])
            ->when($request->input('search'), function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('nip', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('study_program_id'), fn ($q, $id) => $q->where('study_program_id', $id))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.lecturers.index', [
            'lecturers' => $lecturers,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
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
