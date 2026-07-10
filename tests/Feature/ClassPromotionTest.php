<?php

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Services\ClassPromotionService;

/**
 * Helper: buat prodi + 1 kelas di tahun asal.
 */
function makeClass(int $yearLevel = 1, string $degree = 'D3', int $totalSemesters = 6): ClassGroup
{
    $prodi = StudyProgram::factory()->create([
        'code' => 'MI', 'degree_level' => $degree, 'total_semesters' => $totalSemesters,
    ]);

    return ClassGroup::factory()->create([
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => $yearLevel,
        'class_letter' => 'A',
        'class_code' => "MI{$yearLevel}A",
    ]);
}

test('mahasiswa aktif naik kelas dan semester bertambah 2', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    $newClass = ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->first();
    expect($newClass)->not->toBeNull()
        ->and($newClass->year_level)->toBe(2);

    $student->refresh();
    expect($student->class_group_id)->toBe($newClass->id)
        ->and($student->current_semester)->toBe(3);
});

test('mahasiswa cuti tidak ikut dipindah', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Cuti,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('mahasiswa DO tetap di kelas terakhir', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::DO,
    ]);

    app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('kelas tahun akhir tidak dinaikkan (lulus)', function () {
    $class = makeClass(yearLevel: 3, degree: 'D3', totalSemesters: 6); // D3 maks tahun 3
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 5,
        'status' => StudentStatus::Aktif,
    ]);

    $summary = app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($summary['classes_graduated'])->toBe(1)
        ->and($summary['classes_promoted'])->toBe(0)
        ->and(ClassGroup::where('academic_year', '2026/2027')->exists())->toBeFalse();

    expect($student->fresh()->class_group_id)->toBe($class->id);
});

test('D4 boleh naik sampai tahun 4', function () {
    $class = makeClass(yearLevel: 3, degree: 'D4', totalSemesters: 8); // D4 maks tahun 4
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 5,
        'status' => StudentStatus::Aktif,
    ]);

    $summary = app(ClassPromotionService::class)->promote('2025/2026', '2026/2027');

    expect($summary['classes_promoted'])->toBe(1)
        ->and(ClassGroup::where('class_code', 'MI4A')->exists())->toBeTrue();
    expect($student->fresh()->current_semester)->toBe(7);
});

test('promosi idempotent bila dijalankan dua kali', function () {
    $class = makeClass(yearLevel: 1);
    $student = Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    $service = app(ClassPromotionService::class);
    $service->promote('2025/2026', '2026/2027');
    $service->promote('2025/2026', '2026/2027');

    // Kelas tujuan tidak dobel; semester hanya +2 sekali (bukan +4).
    expect(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->count())->toBe(1);
    expect($student->fresh()->current_semester)->toBe(3);
});

test('command class:promote berjalan dan memindahkan mahasiswa', function () {
    $class = makeClass(yearLevel: 1);
    Student::factory()->create([
        'study_program_id' => $class->study_program_id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
        'status' => StudentStatus::Aktif,
    ]);

    $this->artisan('class:promote', ['fromYear' => '2025/2026', 'toYear' => '2026/2027'])
        ->assertSuccessful();

    expect(ClassGroup::where('academic_year', '2026/2027')->where('class_code', 'MI2A')->exists())->toBeTrue();
});
