<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\Lecturer;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

/**
 * Semua foreign key dibuat dengan `constrained()` tanpa `onDelete`, jadi database
 * menolak penghapusan induk yang masih punya anak. Tanpa pengecekan di controller,
 * exception-nya lolos ke halaman error dan admin melihat HTTP 500.
 *
 * Test ini memastikan penolakannya jadi pesan yang bisa dibaca, dan datanya utuh.
 */
beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('prodi yang masih punya mata kuliah tidak bisa dihapus', function () {
    $prodi = StudyProgram::factory()->create();
    Course::factory()->create(['study_program_id' => $prodi->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.study-programs.destroy', $prodi))
        ->assertSessionHas('error');

    $this->assertModelExists($prodi);
});

test('pesan penolakan menyebut jumlah dan jenis data yang menahannya', function () {
    $prodi = StudyProgram::factory()->create();
    Course::factory()->count(2)->create(['study_program_id' => $prodi->id]);
    ClassGroup::factory()->create(['study_program_id' => $prodi->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.study-programs.destroy', $prodi))
        ->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, '2 mata kuliah')
            && str_contains($pesan, '1 kelas'));
});

test('kelas yang masih punya mahasiswa tidak bisa dihapus', function () {
    $class = ClassGroup::factory()->create();
    Student::factory()->create(['class_group_id' => $class->id]);

    $this->actingAs($this->admin)
        ->delete(route('admin.class-groups.destroy', $class))
        ->assertSessionHas('error');

    $this->assertModelExists($class);
});

test('mata kuliah yang masih ditugaskan tidak bisa dihapus', function () {
    $assignment = CourseClassAssignment::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('admin.courses.destroy', $assignment->course))
        ->assertSessionHas('error');

    $this->assertModelExists($assignment->course);
});

test('dosen yang masih punya penugasan tidak bisa dihapus', function () {
    $assignment = CourseClassAssignment::factory()->create();
    $lecturer = $assignment->lecturer;

    $this->actingAs($this->admin)
        ->delete(route('admin.lecturers.destroy', $lecturer))
        ->assertSessionHas('error');

    $this->assertModelExists($lecturer);
    // Akun user-nya juga tidak boleh ikut terhapus separuh jalan.
    $this->assertModelExists($lecturer->user);
});

test('data induk tanpa relasi tetap bisa dihapus seperti biasa', function () {
    $prodi = StudyProgram::factory()->create();
    $class = ClassGroup::factory()->create();
    $course = Course::factory()->create();
    $lecturer = Lecturer::factory()->create();

    $this->actingAs($this->admin)->delete(route('admin.class-groups.destroy', $class))->assertSessionHas('success');
    $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course))->assertSessionHas('success');
    $this->actingAs($this->admin)->delete(route('admin.lecturers.destroy', $lecturer))->assertSessionHas('success');
    $this->actingAs($this->admin)->delete(route('admin.study-programs.destroy', $prodi))->assertSessionHas('success');

    $this->assertModelMissing($class);
    $this->assertModelMissing($course);
    $this->assertModelMissing($lecturer);
    $this->assertModelMissing($prodi);
});
