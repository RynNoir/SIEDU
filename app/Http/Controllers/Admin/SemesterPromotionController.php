<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SemesterType;
use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Services\SemesterPromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SemesterPromotionController extends Controller
{
    public function index(SemesterPromotionService $service): View
    {
        return view('admin.semester-promotion.index', [
            'blockingPeriod' => $service->blockingPeriod(),
            'academicYears' => $this->availableAcademicYears(),
            'preview' => null,
        ]);
    }

    /**
     * Pratinjau (dry-run): tampilkan siapa pindah ke mana sebelum dieksekusi.
     * Sengaja GET & read-only supaya aman di-refresh berkali-kali.
     */
    public function preview(Request $request, SemesterPromotionService $service): View
    {
        $validated = $this->validatePeriod($request);
        $semesterType = SemesterType::from($validated['semester_type']);

        return view('admin.semester-promotion.index', [
            'blockingPeriod' => $service->blockingPeriod(),
            'academicYears' => $this->availableAcademicYears(),
            'preview' => $service->preview($validated['academic_year'], $semesterType),
        ]);
    }

    public function run(Request $request, SemesterPromotionService $service): RedirectResponse
    {
        if ($blocking = $service->blockingPeriod()) {
            return back()->with('error', sprintf(
                'Tutup dulu periode evaluasi "%s" sebelum menaikkan semester — mahasiswa yang pindah kelas akan kehilangan akses ke evaluasi yang belum diisi.',
                $blocking->name,
            ));
        }

        $validated = $this->validatePeriod($request);
        $semesterType = SemesterType::from($validated['semester_type']);
        $summary = $service->promote($validated['academic_year'], $semesterType);

        return redirect()->route('admin.semester-promotion.index')->with('success', sprintf(
            'Promosi %s %s → %s %s selesai: %d mahasiswa naik semester, %d lulus, %d kelas baru dibuat.',
            $semesterType->value, $validated['academic_year'],
            $summary['to_semester_type']->value, $summary['to_academic_year'],
            $summary['students_promoted'], $summary['students_graduated'], $summary['classes_created'],
        ));
    }

    /**
     * @return array{academic_year:string, semester_type:string}
     */
    private function validatePeriod(Request $request): array
    {
        return $request->validate([
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_type' => ['required', Rule::enum(SemesterType::class)],
        ], [
            'academic_year.regex' => 'Format tahun ajaran: 2025/2026.',
        ]);
    }

    /**
     * Tahun ajaran yang benar-benar punya kelas — dipakai sebagai pilihan dropdown
     * supaya admin tidak bisa salah ketik tahun yang tak ada isinya.
     *
     * @return Collection<int, string>
     */
    private function availableAcademicYears(): Collection
    {
        return ClassGroup::query()
            ->distinct()
            ->orderByDesc('academic_year')
            ->pluck('academic_year');
    }
}
