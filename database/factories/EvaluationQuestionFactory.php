<?php

namespace Database\Factories;

use App\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationQuestion>
 */
class EvaluationQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement([
                'Penguasaan & Penyampaian Materi',
                'Interaksi & Ketersediaan',
                'Kedisiplinan & Profesionalisme',
            ]),
            'question_text' => 'Bagaimana penilaian Anda terhadap '.fake()->words(3, true).'?',
            'order_number' => fake()->unique()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
