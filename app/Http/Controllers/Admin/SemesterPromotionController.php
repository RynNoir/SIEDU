<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SemesterType;
use App\Http\Controllers\Controller;
use App\Services\SemesterPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SemesterPromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.semester-promotion.index');
    }

    public function run(Request $request, SemesterPromotionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_type' => ['required', Rule::enum(SemesterType::class)],
        ], [
            'academic_year.regex' => 'Format tahun ajaran: 2025/2026.',
        ]);

        $semesterType = SemesterType::from($validated['semester_type']);
        $summary = $service->promote($validated['academic_year'], $semesterType);

        return back()->with('success', sprintf(
            'Promosi %s %s → %s %s selesai: %d mahasiswa naik semester, %d selesai semester akhir, %d kelas baru dibuat.',
            $semesterType->value, $validated['academic_year'],
            $summary['to_semester_type']->value, $summary['to_academic_year'],
            $summary['students_promoted'], $summary['students_graduated'], $summary['classes_created'],
        ));
    }
}
