<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;

function assignmentPayload(): array
{
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]); // cocok tahun 1
    $period = EvaluationPeriod::factory()->open()->create();

    return compact('prodi', 'class', 'course', 'period');
}

test('team teaching: dua dosen boleh untuk MK+kelas yang sama', function () {
    $admin = User::factory()->admin()->create();
    ['class' => $class, 'course' => $course, 'period' => $period, 'prodi' => $prodi] = assignmentPayload();
    $dosenA = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $dosenB = Lecturer::factory()->create(['study_program_id' => $prodi->id]);

    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id];

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenA->id])
        ->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenB->id])
        ->assertRedirect();

    expect(CourseClassAssignment::count())->toBe(2);
});

test('dosen sama tak boleh diassign dobel (unik 4-kolom)', function () {
    $admin = User::factory()->admin()->create();
    ['class' => $class, 'course' => $course, 'period' => $period, 'prodi' => $prodi] = assignmentPayload();
    $dosen = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id, 'lecturer_id' => $dosen->id];

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), $base)->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), $base)->assertSessionHasErrors('lecturer_id');
});

test('MK semester tak cocok tahun kelas ditolak (§7.1)', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]); // sem 1/2
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 3]); // tahun 2
    $period = EvaluationPeriod::factory()->open()->create();
    $dosen = Lecturer::factory()->create(['study_program_id' => $prodi->id]);

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [
        'course_id' => $course->id, 'class_group_id' => $class->id,
        'evaluation_period_id' => $period->id, 'lecturer_id' => $dosen->id,
    ])->assertSessionHasErrors('course_id');
});
