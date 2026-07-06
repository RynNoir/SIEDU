<?php

namespace Database\Factories;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'nim' => fake()->unique()->numerify('##########'),
            'name' => fake()->name(),
            'study_program_id' => StudyProgram::factory(),
            'class_group_id' => ClassGroup::factory(),
            'current_semester' => fake()->numberBetween(1, 8),
            'status' => StudentStatus::Aktif,
            'created_by' => User::factory()->admin(),
        ];
    }
}
