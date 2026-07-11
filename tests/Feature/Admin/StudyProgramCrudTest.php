<?php

use App\Models\StudyProgram;
use App\Models\User;

test('non-admin ditolak dari CRUD prodi', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.study-programs.index'))->assertForbidden();
});

test('admin melihat daftar prodi', function () {
    $admin = User::factory()->admin()->create();
    StudyProgram::factory()->create(['code' => 'MI', 'name' => 'Manajemen Informatika']);

    $this->actingAs($admin)->get(route('admin.study-programs.index'))
        ->assertOk()
        ->assertSee('MI');
});

test('admin menambah prodi dan total_semesters diturunkan dari jenjang', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.study-programs.store'), [
        'code' => 'TRPL',
        'name' => 'Teknologi Rekayasa Perangkat Lunak',
        'degree_level' => 'D4',
    ])->assertRedirect(route('admin.study-programs.index'));

    $this->assertDatabaseHas('study_programs', [
        'code' => 'TRPL',
        'degree_level' => 'D4',
        'total_semesters' => 8,
    ]);
});

test('kode prodi wajib unik', function () {
    $admin = User::factory()->admin()->create();
    StudyProgram::factory()->create(['code' => 'MI']);

    $this->actingAs($admin)->post(route('admin.study-programs.store'), [
        'code' => 'MI',
        'name' => 'Duplikat',
        'degree_level' => 'D3',
    ])->assertSessionHasErrors('code');
});

test('admin menghapus prodi', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();

    $this->actingAs($admin)->delete(route('admin.study-programs.destroy', $prodi))
        ->assertRedirect(route('admin.study-programs.index'));

    $this->assertModelMissing($prodi);
});
