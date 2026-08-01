<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

/**
 * Isi CSV yang diunduh, sebagai string biasa.
 */
function csvOf(TestResponse $response): string
{
    return $response->streamedContent();
}

/**
 * Satu penugasan lengkap dengan sejumlah mahasiswa yang sudah mengisi evaluasi.
 *
 * @return array{assignment: CourseClassAssignment, students: Collection<int, Student>}
 */
function seedEvaluated(int $respondents, int $rating = 5, ?StudyProgram $prodi = null): array
{
    $prodi ??= StudyProgram::factory()->create();
    $assignment = CourseClassAssignment::factory()->create([
        'lecturer_id' => Lecturer::factory()->create(['study_program_id' => $prodi->id]),
    ]);
    $assignment->classGroup->update(['study_program_id' => $prodi->id]);
    $question = EvaluationQuestion::factory()->create(['category' => 'Penguasaan Materi']);

    $students = Student::factory()->count($respondents)->create([
        'class_group_id' => $assignment->class_group_id,
        'study_program_id' => $prodi->id,
    ]);

    foreach ($students as $student) {
        $evaluation = Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
        EvaluationAnswer::factory()->create([
            'evaluation_id' => $evaluation->id,
            'evaluation_question_id' => $question->id,
            'star_rating' => $rating,
        ]);
        EvaluationImpression::factory()->create([
            'evaluation_id' => $evaluation->id,
            'impression_text' => 'Penjelasannya runtut.',
            'suggestion_text' => 'Slide dibagikan lebih awal.',
        ]);
    }

    return ['assignment' => $assignment->fresh(), 'students' => $students];
}

test('kaprodi mengunduh rekap berisi mata kuliah, dosen, dan skor', function () {
    $prodi = StudyProgram::factory()->create(['code' => 'MI']);
    ['assignment' => $assignment] = seedEvaluated(respondents: 5, prodi: $prodi);
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);

    $response = $this->actingAs($kaprodi)->get(route('kaprodi.dashboard.export'));
    $response->assertOk();

    $csv = csvOf($response);
    expect($csv)->toContain($assignment->course->name)
        ->toContain($assignment->lecturer->name)
        ->toContain($assignment->classGroup->class_code)
        ->toContain('Rata-rata Skor')
        ->toContain('5,0');
});

test('rekap kaprodi hanya memuat penugasan dari prodinya sendiri', function () {
    $mi = StudyProgram::factory()->create(['code' => 'MI']);
    $tk = StudyProgram::factory()->create(['code' => 'TK']);
    ['assignment' => $milik] = seedEvaluated(respondents: 2, prodi: $mi);
    ['assignment' => $prodiLain] = seedEvaluated(respondents: 2, prodi: $tk);
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $mi->id]);

    $csv = csvOf($this->actingAs($kaprodi)->get(route('kaprodi.dashboard.export')));

    expect($csv)->toContain($milik->lecturer->name)
        ->not->toContain($prodiLain->lecturer->name);
});

test('rekap menghormati filter yang sedang aktif', function () {
    $prodi = StudyProgram::factory()->create();
    ['assignment' => $a] = seedEvaluated(respondents: 2, prodi: $prodi);
    ['assignment' => $b] = seedEvaluated(respondents: 2, prodi: $prodi);
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);

    $csv = csvOf($this->actingAs($kaprodi)->get(
        route('kaprodi.dashboard.export', ['lecturer_id' => $a->lecturer_id])
    ));

    expect($csv)->toContain($a->lecturer->name)->not->toContain($b->lecturer->name);
});

test('dosen hanya bisa mengunduh rekap kelasnya sendiri', function () {
    ['assignment' => $milik] = seedEvaluated(respondents: 2);
    ['assignment' => $dosenLain] = seedEvaluated(respondents: 2);

    $csv = csvOf($this->actingAs($milik->lecturer->user)->get(route('lecturer.dashboard.export')));

    expect($csv)->toContain($milik->course->name)
        ->not->toContain($dosenLain->course->name);
});

test('ekspor detail memuat skor per kategori dan kesan & saran', function () {
    ['assignment' => $assignment] = seedEvaluated(respondents: 5);

    $csv = csvOf($this->actingAs($assignment->lecturer->user)
        ->get(route('lecturer.assignments.export', $assignment)));

    expect($csv)->toContain('Penguasaan Materi')
        ->toContain('Penjelasannya runtut.')
        ->toContain('Slide dibagikan lebih awal.');
});

test('ekspor detail menahan kesan & saran di bawah ambang anonimitas', function () {
    config(['evaluation.anonymity_min_respondents' => 5]);
    ['assignment' => $assignment] = seedEvaluated(respondents: 2);

    $csv = csvOf($this->actingAs($assignment->lecturer->user)
        ->get(route('lecturer.assignments.export', $assignment)));

    // Skornya tetap boleh dibaca, tapi teks kesan & saran tidak boleh ikut.
    expect($csv)->toContain('Penguasaan Materi')
        ->toContain('minimal 5 responden')
        ->not->toContain('Penjelasannya runtut.')
        ->not->toContain('Slide dibagikan lebih awal.');
});

test('CSV tidak pernah memuat identitas mahasiswa', function () {
    ['assignment' => $assignment, 'students' => $students] = seedEvaluated(respondents: 5);

    $detail = csvOf($this->actingAs($assignment->lecturer->user)
        ->get(route('lecturer.assignments.export', $assignment)));
    $rekap = csvOf($this->actingAs($assignment->lecturer->user)
        ->get(route('lecturer.dashboard.export')));

    foreach ($students as $student) {
        expect($detail)->not->toContain($student->nim)->not->toContain($student->name);
        expect($rekap)->not->toContain($student->nim)->not->toContain($student->name);
    }
});

test('dosen tidak bisa mengunduh detail penugasan dosen lain', function () {
    ['assignment' => $milikOrangLain] = seedEvaluated(respondents: 5);
    $penyusup = Lecturer::factory()->create();

    $this->actingAs($penyusup->user)
        ->get(route('lecturer.assignments.export', $milikOrangLain))
        ->assertForbidden();
});

test('mahasiswa tidak punya akses ke endpoint ekspor', function () {
    ['assignment' => $assignment] = seedEvaluated(respondents: 5);
    $student = Student::factory()->create();

    $this->actingAs($student->user)->get(route('lecturer.dashboard.export'))->assertForbidden();
    $this->actingAs($student->user)->get(route('kaprodi.dashboard.export'))->assertForbidden();
    $this->actingAs($student->user)
        ->get(route('lecturer.assignments.export', $assignment))->assertForbidden();
});

test('tombol unduh muncul di dashboard dan halaman detail', function () {
    ['assignment' => $assignment] = seedEvaluated(respondents: 5);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.dashboard'))
        ->assertOk()->assertSee(route('lecturer.dashboard.export'), escape: false);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()->assertSee(route('lecturer.assignments.export', $assignment), escape: false);
});

test('tautan unduh di dashboard membawa filter yang sedang aktif', function () {
    ['assignment' => $assignment] = seedEvaluated(respondents: 2);

    $this->actingAs($assignment->lecturer->user)
        ->get(route('lecturer.dashboard', ['period_id' => $assignment->evaluation_period_id]))
        ->assertOk()
        ->assertSee(
            route('lecturer.dashboard.export', ['period_id' => $assignment->evaluation_period_id]),
            escape: false,
        );
});

test('CSV diawali BOM UTF-8 dan memakai pemisah titik koma', function () {
    ['assignment' => $assignment] = seedEvaluated(respondents: 2);

    $csv = csvOf($this->actingAs($assignment->lecturer->user)->get(route('lecturer.dashboard.export')));

    // fputcsv mengutip nilai yang mengandung spasi, jadi header tampil sebagai
    // Periode;"Tahun Ajaran";Semester — itu CSV yang benar, bukan kebocoran format.
    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Periode;"Tahun Ajaran";Semester')
        ->toContain('"Rata-rata Skor"');
});
