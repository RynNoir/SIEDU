<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

test('filter mata kuliah: pencarian kode/nama dan prodi', function () {
    $admin = User::factory()->admin()->create();
    $mi = StudyProgram::factory()->create(['code' => 'MI']);
    $tk = StudyProgram::factory()->create(['code' => 'TK']);
    Course::factory()->create(['study_program_id' => $mi->id, 'code' => 'MI101', 'name' => 'Basis Data', 'semester' => 1]);
    Course::factory()->create(['study_program_id' => $tk->id, 'code' => 'TK101', 'name' => 'Jaringan Komputer', 'semester' => 1]);

    $this->actingAs($admin)->get(route('admin.courses.index', ['search' => 'Basis Data']))
        ->assertOk()->assertSee('MI101')->assertDontSee('TK101');

    $this->actingAs($admin)->get(route('admin.courses.index', ['study_program_id' => $tk->id]))
        ->assertOk()->assertSee('TK101')->assertDontSee('MI101');

    $this->actingAs($admin)->get(route('admin.courses.index', ['semester' => 1]))
        ->assertOk()->assertSee('MI101')->assertSee('TK101');
});

test('filter dosen: pencarian nama/nip dan prodi', function () {
    $admin = User::factory()->admin()->create();
    $mi = StudyProgram::factory()->create(['code' => 'MI']);
    $tk = StudyProgram::factory()->create(['code' => 'TK']);
    Lecturer::factory()->create(['study_program_id' => $mi->id, 'name' => 'Budi Santoso', 'nip' => '111']);
    Lecturer::factory()->create(['study_program_id' => $tk->id, 'name' => 'Citra Dewi', 'nip' => '222']);

    $this->actingAs($admin)->get(route('admin.lecturers.index', ['search' => 'Budi']))
        ->assertOk()->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');

    $this->actingAs($admin)->get(route('admin.lecturers.index', ['study_program_id' => $tk->id]))
        ->assertOk()->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
});

test('filter mahasiswa: kombinasi kelas dan status', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();
    $classA = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'class_code' => 'MI1A']);
    $classB = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'class_code' => 'MI1B']);
    Student::factory()->create(['study_program_id' => $prodi->id, 'class_group_id' => $classA->id, 'nim' => '2401001', 'status' => 'aktif']);
    Student::factory()->create(['study_program_id' => $prodi->id, 'class_group_id' => $classB->id, 'nim' => '2401002', 'status' => 'cuti']);

    $this->actingAs($admin)->get(route('admin.students.index', ['class_group_id' => $classA->id]))
        ->assertOk()->assertSee('2401001')->assertDontSee('2401002');

    $this->actingAs($admin)->get(route('admin.students.index', ['status' => 'cuti']))
        ->assertOk()->assertSee('2401002')->assertDontSee('2401001');
});

test('filter penugasan dosen: per mata kuliah, dosen, kelas, dan periode', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id]);
    $courseA = Course::factory()->create(['study_program_id' => $prodi->id, 'code' => 'CA1']);
    $courseB = Course::factory()->create(['study_program_id' => $prodi->id, 'code' => 'CB1']);
    $dosenA = Lecturer::factory()->create(['study_program_id' => $prodi->id, 'name' => 'Dosen A']);
    $dosenB = Lecturer::factory()->create(['study_program_id' => $prodi->id, 'name' => 'Dosen B']);
    $period = EvaluationPeriod::factory()->create();

    CourseClassAssignment::factory()->create([
        'course_id' => $courseA->id, 'lecturer_id' => $dosenA->id,
        'class_group_id' => $class->id, 'evaluation_period_id' => $period->id,
    ]);
    CourseClassAssignment::factory()->create([
        'course_id' => $courseB->id, 'lecturer_id' => $dosenB->id,
        'class_group_id' => $class->id, 'evaluation_period_id' => $period->id,
    ]);

    // assertDontSee tak dipakai di sini: nama dosen/kode MK tetap muncul di <option> dropdown
    // filter meski barisnya sendiri sudah tersaring, jadi dicek lewat data view (bukan string HTML).
    $byLecturer = $this->actingAs($admin)->get(route('admin.course-class-assignments.index', ['lecturer_id' => $dosenA->id]))
        ->assertOk();
    expect($byLecturer->viewData('assignments')->pluck('lecturer_id')->all())->toBe([$dosenA->id]);

    $byCourse = $this->actingAs($admin)->get(route('admin.course-class-assignments.index', ['course_id' => $courseB->id]))
        ->assertOk();
    expect($byCourse->viewData('assignments')->pluck('course_id')->all())->toBe([$courseB->id]);
});

test('filter dashboard dosen: per mata kuliah dan kelas (dibatasi ke penugasan sendiri)', function () {
    $prodi = StudyProgram::factory()->create();
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id]);
    $courseA = Course::factory()->create(['study_program_id' => $prodi->id, 'code' => 'DA1']);
    $courseB = Course::factory()->create(['study_program_id' => $prodi->id, 'code' => 'DB1']);
    $dosen = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $periodA = EvaluationPeriod::factory()->create();

    CourseClassAssignment::factory()->create([
        'course_id' => $courseA->id, 'lecturer_id' => $dosen->id,
        'class_group_id' => $class->id, 'evaluation_period_id' => $periodA->id,
    ]);
    CourseClassAssignment::factory()->create([
        'course_id' => $courseB->id, 'lecturer_id' => $dosen->id,
        'class_group_id' => $class->id, 'evaluation_period_id' => $periodA->id,
    ]);

    $byCourse = $this->actingAs($dosen->user)->get(route('lecturer.dashboard', ['course_id' => $courseA->id]))
        ->assertOk();
    expect($byCourse->viewData('assignments')->pluck('course_id')->all())->toBe([$courseA->id]);
});
