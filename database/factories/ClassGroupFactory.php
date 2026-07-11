<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassGroup>
 */
class ClassGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'academic_year' => '2025/2026',
            'year_level' => fake()->numberBetween(1, 4),
            'class_letter' => fake()->randomElement(['A', 'B', 'C']),
            'class_code' => strtoupper(fake()->unique()->bothify('??#?')),
            'capacity' => 25,
        ];
    }
}
