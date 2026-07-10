<?php

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use App\Models\User;

test('menambah kelas meng-generate class_code otomatis', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => 1,
        'class_letter' => 'a',
        'capacity' => 25,
    ])->assertRedirect(route('admin.class-groups.index'));

    $this->assertDatabaseHas('class_groups', ['class_code' => 'MI1A', 'class_letter' => 'A']);
});

test('class_code tidak boleh dobel dalam satu tahun ajaran', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    ClassGroup::factory()->create([
        'study_program_id' => $prodi->id, 'academic_year' => '2025/2026',
        'year_level' => 1, 'class_letter' => 'A', 'class_code' => 'MI1A',
    ]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => 1,
        'class_letter' => 'A',
        'capacity' => 25,
    ])->assertSessionHasErrors('class_letter');
});

test('year_level tidak boleh melebihi jenjang prodi', function () {
    $admin = User::factory()->admin()->create();
    $d3 = StudyProgram::factory()->create(['code' => 'MI', 'degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $d3->id,
        'academic_year' => '2025/2026',
        'year_level' => 4, // D3 maksimal tahun 3
        'class_letter' => 'A',
        'capacity' => 25,
    ])->assertSessionHasErrors('year_level');
});
