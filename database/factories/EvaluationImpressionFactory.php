<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationImpression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationImpression>
 */
class EvaluationImpressionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'impression_text' => fake()->sentence(),
            'suggestion_text' => fake()->sentence(),
        ];
    }
}
