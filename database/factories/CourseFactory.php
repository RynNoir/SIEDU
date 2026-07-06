<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'study_program_id' => StudyProgram::factory(),
            'name' => fake()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('??###')),
            'semester' => fake()->numberBetween(1, 8),
            'credit_hours' => fake()->numberBetween(2, 4),
        ];
    }
}