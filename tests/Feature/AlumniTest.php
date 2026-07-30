<?php

use App\Enums\SemesterType;
use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\SemesterPromotionService;

/**
 * Helper: satu alumni di prodi & angkatan tertentu.
 */
function makeAlumnus(StudyProgram $prodi, string $nim, int $enrollmentYear): Student
{
    $class = ClassGroup::factory()->create([
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => 3,
        'class_code' => 'X'.$nim,
    ]);

    return Student::factory()->create([
        'study_program_id' => $prodi->id,
        'class_group_id' => $class->id,
        'nim' => $nim,
        'enrollment_year' => $enrollmentYear,
        'current_semester' => 6,
        'status' => StudentStatus::Lulus,
    ]);
}

test('halaman alumni hanya menampilkan mahasiswa berstatus lulus', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI']);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id]);

    makeAlumnus($prodi, '2201001', 2022);
    Student::factory()->create([
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'nim' => '2401001', 'status' => StudentStatus::Aktif,
    ]);

    $this->actingAs($admin)->get(route('admin.alumni.index'))
        ->assertOk()
        ->assertSee('2201001')
        ->assertDontSee('2401001');
});

test('filter alumni: per angkatan dan per prodi', function () {
    $admin = User::factory()->admin()->create();
    $mi = StudyProgram::factory()->create(['code' => 'MI']);
    $tk = StudyProgram::factory()->create(['code' => 'TK']);

    makeAlumnus($mi, '2201001', 2022);
    makeAlumnus($mi, '2301001', 2023);
    makeAlumnus($tk, '2201002', 2022);

    $this->actingAs($admin)->get(route('admin.alumni.index', ['enrollment_year' => 2022]))
        ->assertOk()->assertSee('2201001')->assertSee('2201002')->assertDontSee('2301001');

    $this->actingAs($admin)->get(route('admin.alumni.index', ['study_program_id' => $tk->id]))
        ->assertOk()->assertSee('2201002')->assertDontSee('2201001');
});

test('filter alumni: pencarian nim dan nama', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI']);

    $budi = makeAlumnus($prodi, '2201001', 2022);
    $budi->update(['name' => 'Budi Santoso']);
    makeAlumnus($prodi, '2201002', 2022)->update(['name' => 'Citra Dewi']);

    $this->actingAs($admin)->get(route('admin.alumni.index', ['search' => 'Budi']))
        ->assertOk()->assertSee('2201001')->assertDontSee('2201002');
});

test('mahasiswa yang diluluskan promosi semester langsung muncul di alumni', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    $class = ClassGroup::factory()->create([
        'study_program_id' => $prodi->id, 'academic_year' => '2025/2026',
        'year_level' => 3, 'class_letter' => 'A', 'class_code' => 'MI3A',
    ]);
    Student::factory()->create([
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'nim' => '2201001', 'current_semester' => 6, 'status' => StudentStatus::Aktif,
    ]);

    // Sebelum promosi: masih di daftar mahasiswa, belum jadi alumni.
    $this->actingAs($admin)->get(route('admin.students.index'))->assertSee('2201001');
    $this->actingAs($admin)->get(route('admin.alumni.index'))->assertDontSee('2201001');

    app(SemesterPromotionService::class)->promote('2025/2026', SemesterType::Genap);

    // Sesudah promosi: berpindah halaman, bukan hilang.
    $this->actingAs($admin)->get(route('admin.students.index'))->assertDontSee('2201001');
    $this->actingAs($admin)->get(route('admin.alumni.index'))->assertSee('2201001');
});

test('halaman alumni tertutup untuk non-admin', function () {
    $student = Student::factory()->create();

    $this->actingAs($student->user)->get(route('admin.alumni.index'))->assertForbidden();
});
