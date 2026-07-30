<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar alumni — mahasiswa yang sudah menuntaskan studinya (status `lulus`).
 *
 * Dipisahkan dari CRUD mahasiswa karena jumlahnya menumpuk tiap tahun dan
 * kelas terakhirnya membuat daftar kelas di halaman mahasiswa jadi rancu.
 * Read-only: alumni tidak ditambah manual, statusnya diberikan oleh promosi
 * semester. Koreksi (mis. mahasiswa ter-luluskan keliru) dilakukan lewat form
 * edit mahasiswa yang sudah ada, tidak perlu form terpisah.
 */
class AlumniController extends Controller
{
    public function index(Request $request): View
    {
        $alumni = Student::alumni()
            ->with(['studyProgram', 'classGroup'])
            ->when($request->input('search'), function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('nim', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->input('study_program_id'), fn ($q, $id) => $q->where('study_program_id', $id))
            ->when($request->input('enrollment_year'), fn ($q, $year) => $q->where('enrollment_year', $year))
            ->orderByDesc('enrollment_year')
            ->orderBy('nim')
            ->paginate(20)
            ->withQueryString();

        return view('admin.alumni.index', [
            'alumni' => $alumni,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'enrollmentYears' => Student::alumni()->distinct()->orderByDesc('enrollment_year')->pluck('enrollment_year'),
        ]);
    }
}
