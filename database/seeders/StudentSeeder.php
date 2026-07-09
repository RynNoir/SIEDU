<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', Role::Admin)->firstOrFail();

        foreach (ClassGroup::all() as $class) {
            Student::factory()->count(25)->create([
                'study_program_id' => $class->study_program_id,
                'class_group_id' => $class->id,
                // Semester ganjil: tahun 1 -> sem 1, tahun 2 -> sem 3 (PRD §7.1)
                'current_semester' => $class->year_level * 2 - 1,
                'created_by' => $admin->id,
            ]);
        }
    }
}
