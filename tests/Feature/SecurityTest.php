<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use App\Models\User;

/**
 * Assignment + 5 evaluasi berisi kesan/saran.
 */
function assignmentWithResults(): CourseClassAssignment
{
    $assignment = CourseClassAssignment::factory()->create();
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);

    for ($i = 0; $i < 5; $i++) {
        $student = Student::factory()->create(['class_group_id' => $assignment->class_group_id]);
        $eval = Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
        foreach ($questions as $q) {
            EvaluationAnswer::factory()->create(['evaluation_id' => $eval->id, 'evaluation_question_id' => $q->id, 'star_rating' => 4]);
        }
        EvaluationImpression::factory()->create(['evaluation_id' => $eval->id, 'impression_text' => 'Kesan', 'suggestion_text' => 'Saran']);
    }

    return $assignment;
}

test('identitas mahasiswa tidak bocor di endpoint dosen', function () {
    $assignment = assignmentWithResults();
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('identitas mahasiswa tidak bocor di endpoint kaprodi', function () {
    $assignment = assignmentWithResults();
    $kaprodi = User::factory()->kaprodi()->create([
        'study_program_id' => $assignment->classGroup->study_program_id,
    ]);
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('tamu diarahkan ke login di semua area terproteksi', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with([
    '/admin/dashboard',
    '/lecturer/dashboard',
    '/kaprodi/dashboard',
    '/student/evaluations',
]);

test('role tidak bisa mengakses area role lain', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/lecturer/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/kaprodi/dashboard')->assertForbidden();
});
