<?php

use App\Enums\PeriodStatus;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuat seluruh graf model via factory tanpa error', function () {
    $answer = EvaluationAnswer::factory()->create();
    $impression = EvaluationImpression::factory()->create();

    expect($answer->exists)->toBeTrue();
    expect($impression->exists)->toBeTrue();
});

it('menghubungkan relasi student sampai ke prodi & kelas', function () {
    $student = Student::factory()->create();

    expect($student->user)->not->toBeNull();
    expect($student->studyProgram)->not->toBeNull();
    expect($student->classGroup)->not->toBeNull();
    expect($student->creator->isAdmin())->toBeTrue();
});

it('menghubungkan evaluation ke answers & impression', function () {
    $evaluation = Evaluation::factory()
        ->has(EvaluationAnswer::factory()->count(3), 'answers')
        ->has(EvaluationImpression::factory(), 'impression')
        ->create();

    expect($evaluation->answers)->toHaveCount(3);
    expect($evaluation->impression)->not->toBeNull();
    expect($evaluation->student)->not->toBeNull();
});

it('menghubungkan assignment ke course, lecturer, class, period', function () {
    $assignment = CourseClassAssignment::factory()->create();

    expect($assignment->course)->not->toBeNull();
    expect($assignment->lecturer->user->isLecturer())->toBeTrue();
    expect($assignment->classGroup)->not->toBeNull();
    expect($assignment->evaluationPeriod)->not->toBeNull();
    expect($assignment->creator->isAdmin())->toBeTrue();
});

it('menegakkan periode evaluasi tunggal saat activate()', function () {
    $lama = EvaluationPeriod::factory()->open()->create();
    $baru = EvaluationPeriod::factory()->create();

    $baru->activate();

    expect($baru->fresh()->status)->toBe(PeriodStatus::Open);
    expect($lama->fresh()->status)->toBe(PeriodStatus::Closed);
});
