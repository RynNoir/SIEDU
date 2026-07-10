<?php

use App\Models\StudyProgram;
use App\Models\User;

test('mata kuliah semester 7 ditolak untuk prodi D3', function () {
    $admin = User::factory()->admin()->create();
    $d3 = StudyProgram::factory()->create(['degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.courses.store'), [
        'study_program_id' => $d3->id,
        'code' => 'X701',
        'name' => 'MK Semester 7',
        'semester' => 7,
        'credit_hours' => 3,
    ])->assertSessionHasErrors('semester');

    $this->assertDatabaseCount('courses', 0);
});

test('mata kuliah semester 7 diterima untuk prodi D4', function () {
    $admin = User::factory()->admin()->create();
    $d4 = StudyProgram::factory()->create(['degree_level' => 'D4', 'total_semesters' => 8]);

    $this->actingAs($admin)->post(route('admin.courses.store'), [
        'study_program_id' => $d4->id,
        'code' => 'Y701',
        'name' => 'MK Semester 7',
        'semester' => 7,
        'credit_hours' => 3,
    ])->assertRedirect(route('admin.courses.index'));

    $this->assertDatabaseHas('courses', ['code' => 'Y701', 'semester' => 7]);
});
