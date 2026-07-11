<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;

/**
 * Buat assignment + $count evaluasi (jawaban rating tetap + kesan/saran).
 */
function seedResults(CourseClassAssignment $assignment, int $count, int $rating = 4): void
{
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);

    for ($i = 0; $i < $count; $i++) {
        $student = Student::factory()->create(['class_group_id' => $assignment->class_group_id]);
        $eval = Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
        foreach ($questions as $q) {
            EvaluationAnswer::factory()->create([
                'evaluation_id' => $eval->id,
                'evaluation_question_id' => $q->id,
                'star_rating' => $rating,
            ]);
        }
        EvaluationImpression::factory()->create([
            'evaluation_id' => $eval->id,
            'impression_text' => "Kesan nomor {$i}",
            'suggestion_text' => 'Saran uji',
        ]);
    }
}

test('dosen melihat daftar assignment miliknya', function () {
    $assignment = CourseClassAssignment::factory()->create();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.dashboard'))
        ->assertOk()
        ->assertSee($assignment->course->name);
});

test('skor rata-rata keseluruhan dihitung benar', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5, rating: 4);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('4.0'); // semua rating 4 → rata-rata 4.0
});

test('kesan & saran tersembunyi di bawah threshold', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 4); // < 5

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee('Kesan nomor 0')
        ->assertSee('minimal 5 mahasiswa');
});

test('kesan & saran tampil bila threshold terpenuhi', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Kesan nomor 0')
        ->assertSee('Anonim');
});

test('identitas mahasiswa tidak pernah muncul di halaman hasil', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5);
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('dosen lain tidak bisa melihat assignment bukan miliknya', function () {
    $assignment = CourseClassAssignment::factory()->create();
    $lain = Lecturer::factory()->create();

    $this->actingAs($lain->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertForbidden();
});
