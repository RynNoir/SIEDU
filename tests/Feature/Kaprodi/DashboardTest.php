<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

/**
 * Assignment untuk MK & kelas di prodi tertentu.
 */
function assignmentInProdi(StudyProgram $prodi, ?string $courseName = null): CourseClassAssignment
{
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id]);
    $course = Course::factory()->create([
        'study_program_id' => $prodi->id,
        'name' => $courseName ?? fake()->unique()->words(3, true),
    ]);

    return CourseClassAssignment::factory()->create([
        'class_group_id' => $class->id,
        'course_id' => $course->id,
    ]);
}

function seedKaprodiResults(CourseClassAssignment $assignment, int $count): void
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
            EvaluationAnswer::factory()->create(['evaluation_id' => $eval->id, 'evaluation_question_id' => $q->id, 'star_rating' => 4]);
        }
        EvaluationImpression::factory()->create(['evaluation_id' => $eval->id, 'impression_text' => "Kesan {$i}", 'suggestion_text' => 'Saran']);
    }
}

test('kaprodi hanya melihat data prodinya di dashboard', function () {
    $prodiA = StudyProgram::factory()->create();
    $prodiB = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodiA->id]);

    $a = assignmentInProdi($prodiA, 'Basis Data A');
    $b = assignmentInProdi($prodiB, 'Jaringan B');

    $this->actingAs($kaprodi)->get(route('kaprodi.dashboard'))
        ->assertOk()
        ->assertSee('Basis Data A')
        ->assertDontSee('Jaringan B');
});

test('kaprodi tidak bisa melihat detail assignment prodi lain', function () {
    $prodiA = StudyProgram::factory()->create();
    $prodiB = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodiA->id]);

    $assignmentB = assignmentInProdi($prodiB);

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignmentB))
        ->assertForbidden();
});

test('detail kaprodi patuh threshold & anonimitas', function () {
    $prodi = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);
    $assignment = assignmentInProdi($prodi);
    seedKaprodiResults($assignment, count: 5);

    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Kesan 0')
        ->assertSee('Anonim')
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('kesan & saran tersembunyi di bawah threshold untuk kaprodi', function () {
    $prodi = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);
    $assignment = assignmentInProdi($prodi);
    seedKaprodiResults($assignment, count: 4);

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee('Kesan 0')
        ->assertSee('minimal 5 mahasiswa');
});
