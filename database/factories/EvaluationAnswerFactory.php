<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationAnswer>
 */
class EvaluationAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'evaluation_question_id' => EvaluationQuestion::factory(),
            'star_rating' => fake()->numberBetween(1, 5),
        ];
    }
}
