<?php

namespace Database\Factories;

use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lecturer>
 */
class LecturerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->lecturer(),
            'name' => fake()->name(),
            'nip' => fake()->unique()->numerify('##################'),
            'study_program_id' => StudyProgram::factory(),
        ];
    }
}
