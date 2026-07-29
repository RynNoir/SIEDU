<?php

use App\Enums\SemesterType;
use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Services\SemesterPromotionService;

/**
 * Helper: buat prodi + 1 kelas di tahun ajaran asal.
 */
function makeClass(int $yearLevel = 1, string $degree = 'D3', int $totalSemesters = 6, string $academicYear = '2025/2026'): ClassGroup
{
    $prodi = StudyProgram::factory()->create([
        'code' => 'MI', 'degree_level' => $degree, 'total_semesters' => $totalSemesters,
    ]);

    return ClassGroup::factory()->create([
        'study_program_id' => $prodi->id,
        'academic_year' => $academicYear,
        'year_level' => $yearLevel,
        'class_letter' => 'A',
        'class_code' => "MI{$yearLevel}A",
    ]);
}

/**
 * Helper: mahasiswa di kelas tertentu pada semester tertentu.
 */
function makeStudent(ClassGroup $class, int $semester, StudentStatus $status = StudentStatus::Aktif): Student
{
    return Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => $semester,
        'status' => $status,
    ]);
}

test('dari ganjil: naik 1 semester dan tetap di kelas serta tahun ajaran yang sama', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1);

    $summary = app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Ganjil);

    $student->refresh();
    expect($student->current_semester)->toBe(2)
        ->and($student->class_group_id)->toBe($class->id);

    expect($summary['classes_created'])->toBe(0)
        ->and($summary['to_academic_year'])->toBe('2025/2026')
        ->and($summary['to_semester_type'])->toBe(SemesterType::Genap)
        ->and(ClassGroup::where('academic_year', '2026/2027')->exists())->toBeFalse();
});

test('dari genap: naik 1 semester, naik kelas, dan pindah tahun ajaran', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 2);

    $summary = app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    $newClass = ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->first();
    expect($newClass)->not->toBeNull()
        ->and($newClass->year_level)->toBe(2);

    $student->refresh();
    expect($student->current_semester)->toBe(3)
        ->and($student->class_group_id)->toBe($newClass->id);

    expect($summary['classes_created'])->toBe(1)
        ->and($summary['to_academic_year'])->toBe('2026/2027')
        ->and($summary['to_semester_type'])->toBe(SemesterType::Ganjil);
});

test('satu siklus penuh semester 1 sampai 6 butuh 5 kali promosi', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1);
    $service = app(SemesterPromotionService::class);

    $service->promote('2025/2026', SemesterType::Ganjil);   // 1 -> 2, MI1A
    $service->promote('2025/2026', SemesterType::Genap);    // 2 -> 3, MI2A @2026/2027
    $service->promote('2026/2027', SemesterType::Ganjil);   // 3 -> 4, MI2A
    $service->promote('2026/2027', SemesterType::Genap);    // 4 -> 5, MI3A @2027/2028
    $service->promote('2027/2028', SemesterType::Ganjil);   // 5 -> 6, MI3A

    $student->refresh();
    expect($student->current_semester)->toBe(6)
        ->and($student->classGroup->class_code)->toBe('MI3A')
        ->and($student->classGroup->academic_year)->toBe('2027/2028');
});

test('filter paritas menjaga idempotensi saat promosi ganjil dijalankan dua kali', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1);

    $service = app(SemesterPromotionService::class);
    $service->promote('2025/2026', SemesterType::Ganjil);
    $summary = $service->promote('2025/2026', SemesterType::Ganjil);

    // Kelas tidak berpindah di transisi ini, jadi tanpa filter paritas semester
    // akan jadi 3. Yang benar: tetap 2 dan proses kedua tidak melakukan apa pun.
    expect($student->fresh()->current_semester)->toBe(2)
        ->and($summary['students_promoted'])->toBe(0);
});

test('promosi genap idempotent bila dijalankan dua kali', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 2);

    $service = app(SemesterPromotionService::class);
    $service->promote('2025/2026', SemesterType::Genap);
    $service->promote('2025/2026', SemesterType::Genap);

    expect(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->count())->toBe(1)
        ->and($student->fresh()->current_semester)->toBe(3);
});

test('promosi dengan tipe semester yang tidak cocok tidak mengubah apa pun', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1); // semester ganjil

    // Dijalankan sebagai periode genap -> paritas tidak cocok.
    $summary = app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    expect($student->fresh()->current_semester)->toBe(1)
        ->and($summary['students_promoted'])->toBe(0)
        ->and($summary['classes_created'])->toBe(0);
});

test('mahasiswa cuti tidak ikut dipromosikan', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1, status: StudentStatus::Cuti);

    app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Ganjil);

    expect($student->fresh()->current_semester)->toBe(1);
});

test('mahasiswa DO tetap di kelas dan semester terakhir', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1, status: StudentStatus::DO);

    app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Ganjil);

    $student->refresh();
    expect($student->current_semester)->toBe(1)
        ->and($student->class_group_id)->toBe($class->id);
});

test('mahasiswa yang tertinggal setelah cuti mengikuti jadwal semesternya sendiri', function () {
    // Kelas tahun 2 (semester normal 3/4), tapi mahasiswa ini baru di semester 1
    // karena pernah cuti. Promosi ganjil harus tetap menaikkannya ke semester 2.
    $class = makeClass(yearLevel: 2);
    $tertinggal = makeStudent($class, semester: 1);
    $normal = makeStudent($class, semester: 3);

    app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Ganjil);

    expect($tertinggal->fresh()->current_semester)->toBe(2)
        ->and($normal->fresh()->current_semester)->toBe(4);
});

test('mahasiswa di semester terakhir diluluskan, bukan dinaikkan', function () {
    $class = makeClass(yearLevel: 3, degree: 'D3', totalSemesters: 6);
    $student = makeStudent($class, semester: 6);

    $summary = app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    expect($summary['students_graduated'])->toBe(1)
        ->and($summary['students_promoted'])->toBe(0);

    $student->refresh();
    expect($student->status)->toBe(StudentStatus::Lulus)
        // Kelas & semester terakhir dipertahankan sebagai histori.
        ->and($student->current_semester)->toBe(6)
        ->and($student->class_group_id)->toBe($class->id);
});

test('mahasiswa yang sudah lulus tidak ikut promosi berikutnya', function () {
    $class = makeClass(yearLevel: 3, degree: 'D3', totalSemesters: 6);
    $student = makeStudent($class, semester: 6);
    $service = app(SemesterPromotionService::class);

    $service->promote('2025/2026', SemesterType::Genap);
    $summary = $service->promote('2025/2026', SemesterType::Genap);

    expect($summary['students_graduated'])->toBe(0)
        ->and($student->fresh()->status)->toBe(StudentStatus::Lulus);
});

test('kelulusan tidak menghapus tahun angkatan sebagai penanda kohort alumni', function () {
    $class = makeClass(yearLevel: 3, degree: 'D3', totalSemesters: 6);
    $student = makeStudent($class, semester: 6);
    $angkatan = $student->enrollment_year;

    app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    $student->refresh();
    expect($student->status)->toBe(StudentStatus::Lulus)
        ->and($student->enrollment_year)->toBe($angkatan);
});

test('D4 boleh naik sampai semester 8', function () {
    $class = makeClass(yearLevel: 3, degree: 'D4', totalSemesters: 8);
    $student = makeStudent($class, semester: 6);

    $summary = app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    expect($summary['students_promoted'])->toBe(1)
        ->and($summary['students_graduated'])->toBe(0)
        ->and($student->fresh()->current_semester)->toBe(7)
        ->and(ClassGroup::where('class_code', 'MI4A')->exists())->toBeTrue();
});

test('promosi tidak pernah mengubah tahun angkatan', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 1);
    $angkatan = $student->enrollment_year;

    $service = app(SemesterPromotionService::class);
    $service->promote('2025/2026', SemesterType::Ganjil);
    $service->promote('2025/2026', SemesterType::Genap);

    expect($student->fresh()->enrollment_year)->toBe($angkatan);
});

test('command semester:promote menaikkan semester mahasiswa', function () {
    $class = makeClass(yearLevel: 1);
    $student = makeStudent($class, semester: 2);

    $this->artisan('semester:promote', ['academicYear' => '2025/2026', 'semesterType' => 'genap'])
        ->assertSuccessful();

    expect($student->fresh()->current_semester)->toBe(3)
        ->and(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->exists())->toBeTrue();
});

test('command semester:promote menolak tipe semester yang tidak dikenal', function () {
    $this->artisan('semester:promote', ['academicYear' => '2025/2026', 'semesterType' => 'pendek'])
        ->assertFailed();
});
