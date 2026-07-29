<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SemesterPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'from_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'to_year' => ['required', 'regex:/^\d{4}\/\d{4}$/', 'different:from_year'],
        ], [
            'from_year.regex' => 'Format tahun ajaran: 2025/2026.',
            'to_year.regex' => 'Format tahun ajaran: 2026/2027.',
            'to_year.different' => 'Tahun tujuan harus berbeda dari tahun asal.',
        ]);

        $summary = $service->promote($validated['from_year'], $validated['to_year']);

        return back()->with('success', sprintf(
            'Promosi %s → %s selesai: %d kelas naik, %d mahasiswa dipindah, %d kelas lulus.',
            $validated['from_year'], $validated['to_year'],
            $summary['classes_promoted'], $summary['students_promoted'], $summary['classes_graduated'],
        ));
    }
}
