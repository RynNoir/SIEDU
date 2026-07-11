<?php

use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;

test('dashboard admin menampilkan ringkasan jumlah data', function () {
    $admin = User::factory()->admin()->create();
    StudyProgram::factory()->count(3)->create();
    Lecturer::factory()->count(2)->create();

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Program Studi')
        ->assertSee('Dosen')
        ->assertSee('Mahasiswa Aktif');
});

test('non-admin tidak bisa mengakses dashboard admin', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
});
