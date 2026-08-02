<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Services\CsvExportService;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Impor massal mahasiswa dari CSV.
 *
 * Alur tiga langkah, mengikuti pola Promosi Semester: unggah -> pratinjau ->
 * konfirmasi. Tanpa pratinjau, admin yang mengunggah 200 baris cuma menerima
 * satu pesan "gagal" tanpa tahu baris mana yang salah — lebih melelahkan
 * daripada mengetik manual sejak awal.
 *
 * Berkas unggahan disimpan sementara di disk privat antara pratinjau dan
 * konfirmasi. Tokennya disimpan di session, bukan di form, supaya admin lain
 * tidak bisa mengeksekusi berkas yang bukan diunggahnya.
 */
class StudentImportController extends Controller
{
    private const SESSION_KEY = 'student_import_token';

    private const DIRECTORY = 'imports/students';

    public function index(): View
    {
        return view('admin.students.import', [
            'preview' => null,
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
        ]);
    }

    /**
     * Unduh template kosong berisi baris judul kolom + satu baris contoh,
     * supaya admin tidak perlu menebak nama kolom yang diharapkan.
     */
    public function template(CsvExportService $csv, StudentImportService $import): StreamedResponse
    {
        $example = ClassGroup::with('studyProgram')->orderBy('class_code')->first();

        return $csv->download('template-impor-mahasiswa.csv', [
            StudentImportService::COLUMNS,
            [
                '2401001',
                'Budi Santoso',
                'budi@student.kampus.ac.id',
                $example?->studyProgram->code ?? 'MI',
                $example?->class_code ?? 'MI1A',
                now()->year,
                1,
                'aktif',
            ],
        ]);
    }

    /**
     * Periksa berkas tanpa menyimpan apa pun.
     */
    public function preview(Request $request, StudentImportService $import): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ], [], ['file' => 'berkas CSV']);

        $path = $request->file('file')->store(self::DIRECTORY);

        try {
            $preview = $import->preview(Storage::path($path));
        } catch (RuntimeException $e) {
            Storage::delete($path);

            return back()->with('error', $e->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $path);

        return view('admin.students.import', [
            'preview' => $preview,
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
        ]);
    }

    /**
     * Simpan baris yang lolos, lalu bersihkan berkas sementaranya.
     */
    public function store(Request $request, StudentImportService $import): RedirectResponse
    {
        $path = $request->session()->pull(self::SESSION_KEY);

        if (! $path || ! Storage::exists($path)) {
            return redirect()->route('admin.students.import.index')
                ->with('error', 'Berkasnya sudah tidak tersedia. Silakan unggah ulang.');
        }

        $result = $import->import(Storage::path($path), (int) auth()->id());
        Storage::delete($path);

        if ($result['imported'] === 0) {
            return redirect()->route('admin.students.import.index')
                ->with('error', 'Tidak ada baris yang bisa diimpor. Perbaiki berkasnya lalu unggah ulang.');
        }

        return redirect()->route('admin.students.index')->with('success', sprintf(
            '%d mahasiswa berhasil diimpor%s. Password awal: %s — mahasiswa wajib menggantinya saat login pertama.',
            $result['imported'],
            $result['skipped'] > 0 ? ", {$result['skipped']} baris dilewati" : '',
            config('evaluation.default_password'),
        ));
    }
}
