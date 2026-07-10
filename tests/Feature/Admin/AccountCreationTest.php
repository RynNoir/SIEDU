<?php

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use App\Models\User;

test('membuat dosen sekaligus akun user role=lecturer', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();

    $this->actingAs($admin)->post(route('admin.lecturers.store'), [
        'name' => 'Budi Santoso',
        'nip' => '1990010112345',
        'email' => 'budi@siedu.test',
        'study_program_id' => $prodi->id,
    ])->assertRedirect(route('admin.lecturers.index'));

    $this->assertDatabaseHas('users', ['email' => 'budi@siedu.test', 'role' => 'lecturer', 'must_change_password' => true]);
    $this->assertDatabaseHas('lecturers', ['nip' => '1990010112345']);
});

test('membuat mahasiswa menolak semester tak sesuai tahun kelas (§7.1)', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]); // valid: sem 1/2

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Ani', 'nim' => '2401001', 'email' => 'ani@siedu.test',
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'current_semester' => 3, // tidak valid untuk tahun 1
        'status' => 'aktif',
    ])->assertSessionHasErrors('current_semester');
});

test('membuat mahasiswa set created_by ke admin', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Ani', 'nim' => '2401002', 'email' => 'ani2@siedu.test',
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'current_semester' => 1, 'status' => 'aktif',
    ])->assertRedirect(route('admin.students.index'));

    $this->assertDatabaseHas('students', ['nim' => '2401002', 'created_by' => $admin->id]);
});
