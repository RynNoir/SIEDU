<?php

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Services\EvaluationProgressService;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->prodi = StudyProgram::factory()->create(['code' => 'MI']);
    $this->period = EvaluationPeriod::factory()->open()->create();
    $this->service = app(EvaluationProgressService::class);
});

/**
 * Satu kelas berisi sejumlah mahasiswa aktif dan sejumlah penugasan dosen.
 *
 * @return array{class: ClassGroup, students: Collection, assignments: Collection}
 */
function seedClass(string $code, int $students, int $assignments, ?StudyProgram $prodi = null, ?EvaluationPeriod $period = null): array
{
    $prodi ??= test()->prodi;
    $period ??= test()->period;

    $class = ClassGroup::factory()->create([
        'study_program_id' => $prodi->id,
        'class_code' => $code,
        'year_level' => 1,
    ]);

    return [
        'class' => $class,
        'students' => Student::factory()->count($students)->create([
            'class_group_id' => $class->id,
            'study_program_id' => $prodi->id,
            'current_semester' => 1,
        ]),
        'assignments' => CourseClassAssignment::factory()->count($assignments)->create([
            'class_group_id' => $class->id,
            'evaluation_period_id' => $period->id,
        ]),
    ];
}

/**
 * Mahasiswa mengisi sejumlah penugasan pertama di kelasnya.
 */
function submitFor(Student $student, iterable $assignments, int $howMany): void
{
    foreach (collect($assignments)->take($howMany) as $assignment) {
        Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
    }
}

test('tanpa periode terbuka tidak ada progres yang dilaporkan', function () {
    seedClass('MI1A', students: 5, assignments: 2);

    expect($this->service->forPeriod(null))->toBe([]);
});

test('kelas tanpa satu pun evaluasi masuk tampil dengan progres nol', function () {
    seedClass('MI1A', students: 4, assignments: 2);

    $rows = $this->service->forPeriod($this->period);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['class_code'])->toBe('MI1A')
        ->and($rows[0]['study_program'])->toBe('MI')
        ->and($rows[0]['students'])->toBe(4)
        ->and($rows[0]['assignments'])->toBe(2)
        ->and($rows[0]['expected'])->toBe(8)
        ->and($rows[0]['submitted'])->toBe(0)
        ->and($rows[0]['completed'])->toBe(0)
        ->and($rows[0]['percentage'])->toBe(0.0);
});

test('mahasiswa dihitung selesai hanya bila seluruh penugasannya terisi', function () {
    ['students' => $students, 'assignments' => $assignments] = seedClass('MI1A', students: 3, assignments: 3);

    submitFor($students[0], $assignments, 3); // selesai
    submitFor($students[1], $assignments, 2); // belum
    submitFor($students[2], $assignments, 0); // belum

    $rows = $this->service->forPeriod($this->period);

    expect($rows[0]['completed'])->toBe(1)
        ->and($rows[0]['submitted'])->toBe(5)
        ->and($rows[0]['expected'])->toBe(9);
});

test('progres tetap terbaca walau belum ada satu pun mahasiswa yang selesai', function () {
    // Semua mahasiswa kurang satu evaluasi: "selesai" nol, tapi kelasnya
    // sebenarnya sudah hampir tuntas. Inilah alasan dua angka dilaporkan.
    ['students' => $students, 'assignments' => $assignments] = seedClass('MI1A', students: 4, assignments: 2);

    foreach ($students as $student) {
        submitFor($student, $assignments, 1);
    }

    $rows = $this->service->forPeriod($this->period);

    expect($rows[0]['completed'])->toBe(0)
        ->and($rows[0]['percentage'])->toBe(50.0);
});

test('kelas diurutkan dari progres terendah supaya yang perlu ditagih tampil dulu', function () {
    ['students' => $penuh, 'assignments' => $tugasPenuh] = seedClass('MI1A', students: 2, assignments: 1);
    ['students' => $sebagian, 'assignments' => $tugasSebagian] = seedClass('MI1B', students: 4, assignments: 1);
    seedClass('MI1C', students: 3, assignments: 1); // kosong

    foreach ($penuh as $s) {
        submitFor($s, $tugasPenuh, 1);
    }
    submitFor($sebagian[0], $tugasSebagian, 1);

    $rows = $this->service->forPeriod($this->period);

    expect(array_column($rows, 'class_code'))->toBe(['MI1C', 'MI1B', 'MI1A'])
        ->and($rows[0]['percentage'])->toBe(0.0)
        ->and($rows[1]['percentage'])->toBe(25.0)
        ->and($rows[2]['percentage'])->toBe(100.0);
});

test('hanya mahasiswa aktif yang dihitung sebagai target pengisian', function () {
    ['class' => $class] = seedClass('MI1A', students: 2, assignments: 1);
    Student::factory()->create([
        'class_group_id' => $class->id,
        'study_program_id' => $this->prodi->id,
        'current_semester' => 1,
        'status' => StudentStatus::Cuti,
    ]);

    // Mahasiswa cuti tidak wajib mengisi, jadi tak boleh menggelembungkan target.
    expect($this->service->forPeriod($this->period)[0]['students'])->toBe(2);
});

test('kelas tanpa penugasan pada periode ini tidak ikut dilaporkan', function () {
    seedClass('MI1A', students: 3, assignments: 1);
    ClassGroup::factory()->create(['study_program_id' => $this->prodi->id, 'class_code' => 'MI1B']);

    expect(array_column($this->service->forPeriod($this->period), 'class_code'))->toBe(['MI1A']);
});

test('evaluasi dari periode lain tidak ikut terhitung', function () {
    ['students' => $students, 'assignments' => $assignments] = seedClass('MI1A', students: 2, assignments: 1);
    $periodeLama = EvaluationPeriod::factory()->closed()->create();
    $tugasLama = CourseClassAssignment::factory()->create([
        'class_group_id' => $assignments[0]->class_group_id,
        'evaluation_period_id' => $periodeLama->id,
    ]);
    submitFor($students[0], [$tugasLama], 1);

    expect($this->service->forPeriod($this->period)[0]['submitted'])->toBe(0);
});
