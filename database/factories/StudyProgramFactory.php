<?php

namespace Database\Factories;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyProgram>
 */
class StudyProgramFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $degree = fake()->randomElement(DegreeLevel::cases());

        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'degree_level' => $degree,
            'total_semesters' => $degree->totalSemesters(),
        ];
    }
}
