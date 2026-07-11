<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

test('alur penuh: admin assign → mahasiswa isi → dosen lihat hasil', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]);
    $lecturer = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $period = EvaluationPeriod::factory()->open()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);
    $students = Student::factory()->count(5)->create([
        'study_program_id' => $prodi->id,
        'class_group_id' => $class->id,
        'current_semester' => 1,
    ]);

    // 1) Admin membuat penugasan dosen
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [
        'course_id' => $course->id,
        'lecturer_id' => $lecturer->id,
        'class_group_id' => $class->id,
        'evaluation_period_id' => $period->id,
    ])->assertRedirect();

    $assignment = CourseClassAssignment::firstOrFail();

    // 2) 5 mahasiswa mengisi evaluasi (semua rating 5)
    foreach ($students as $student) {
        $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
            'answers' => $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all(),
            'impression_text' => 'Pengajaran bagus.',
            'suggestion_text' => 'Pertahankan.',
        ])->assertRedirect(route('student.evaluations.index'));
    }

    // 3) Dosen melihat hasil — skor & kesan tampil (threshold 5 terpenuhi)
    $this->actingAs($lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('5.0')
        ->assertSee('Anonim');
});

test('alur team teaching: 1 MK 2 dosen tampil sebagai 2 form', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]);
    $period = EvaluationPeriod::factory()->open()->create();
    $dosenA = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $dosenB = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $student = Student::factory()->create(['study_program_id' => $prodi->id, 'class_group_id' => $class->id, 'current_semester' => 1]);

    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id];
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenA->id])->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenB->id])->assertRedirect();

    // Mahasiswa melihat 2 kartu (nama kedua dosen)
    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertOk()
        ->assertSee($dosenA->name)
        ->assertSee($dosenB->name);

    expect(CourseClassAssignment::count())->toBe(2);
});

test('alur promosi kelas menaikkan mahasiswa aktif', function () {
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    $class = ClassGroup::factory()->create([
        'study_program_id' => $prodi->id, 'academic_year' => '2025/2026',
        'year_level' => 1, 'class_letter' => 'A', 'class_code' => 'MI1A',
    ]);
    $student = Student::factory()->create([
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id, 'current_semester' => 1,
    ]);

    $this->artisan('class:promote', ['fromYear' => '2025/2026', 'toYear' => '2026/2027'])->assertSuccessful();

    $newClass = ClassGroup::where('class_code', 'MI2A')->where('academic_year', '2026/2027')->firstOrFail();
    $student->refresh();
    expect($student->class_group_id)->toBe($newClass->id)
        ->and($student->current_semester)->toBe(3);
});
