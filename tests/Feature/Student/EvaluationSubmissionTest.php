<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Setup: mahasiswa + periode open + assignment untuk kelasnya + pertanyaan.
 *
 * @return array{student: Student, assignment: CourseClassAssignment, questions: Collection}
 */
function evalScenario(): array
{
    $period = EvaluationPeriod::factory()->open()->create();
    $student = Student::factory()->create();
    $assignment = CourseClassAssignment::factory()->create([
        'class_group_id' => $student->class_group_id,
        'evaluation_period_id' => $period->id,
    ]);
    $questions = EvaluationQuestion::factory()->count(3)->create(['is_active' => true]);

    return compact('student', 'assignment', 'questions');
}

test('mahasiswa melihat daftar evaluasi kelasnya pada periode open', function () {
    ['student' => $student, 'assignment' => $assignment] = evalScenario();

    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertOk()
        ->assertSee($assignment->course->name)
        ->assertSee($assignment->lecturer->name);
});

test('submit evaluasi membuat evaluation, answers, dan impression', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();

    $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all();

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
        'answers' => $answers,
        'impression_text' => 'Penjelasannya jelas.',
        'suggestion_text' => 'Perbanyak latihan.',
    ])->assertRedirect(route('student.evaluations.index'));

    $evaluation = Evaluation::first();
    expect($evaluation)->not->toBeNull()
        ->and($evaluation->student_id)->toBe($student->id);
    expect($evaluation->answers)->toHaveCount(3);
    expect($evaluation->impression->impression_text)->toBe('Penjelasannya jelas.');
});

test('submit tanpa menilai semua pertanyaan ditolak', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();

    $answers = [$questions->first()->id => 4]; // hanya 1 dari 3

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
        'answers' => $answers,
    ])->assertSessionHasErrors('answers');

    expect(Evaluation::count())->toBe(0);
});

test('cegah submit ganda untuk assignment yang sama', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();
    $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all();

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), ['answers' => $answers]);
    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), ['answers' => $answers]);

    expect(Evaluation::count())->toBe(1);
});

test('team teaching muncul sebagai dua form terpisah', function () {
    ['student' => $student, 'assignment' => $assignment] = evalScenario();
    // Dosen kedua untuk MK+kelas+periode yang sama.
    $dosenB = Lecturer::factory()->create();
    $assignmentB = CourseClassAssignment::factory()->create([
        'course_id' => $assignment->course_id,
        'class_group_id' => $assignment->class_group_id,
        'evaluation_period_id' => $assignment->evaluation_period_id,
        'lecturer_id' => $dosenB->id,
    ]);

    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertSee($assignment->lecturer->name)
        ->assertSee($dosenB->name);

    // Keduanya bisa dibuka.
    $this->actingAs($student->user)->get(route('student.evaluations.show', $assignmentB))->assertOk();
});

test('mahasiswa tidak bisa mengisi assignment kelas lain', function () {
    ['student' => $student] = evalScenario();
    $period = EvaluationPeriod::open()->first();
    $lain = CourseClassAssignment::factory()->create(['evaluation_period_id' => $period->id]); // kelas berbeda

    $this->actingAs($student->user)->get(route('student.evaluations.show', $lain))->assertForbidden();
});
