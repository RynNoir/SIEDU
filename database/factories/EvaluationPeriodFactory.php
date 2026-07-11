<?php

namespace Database\Factories;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationPeriod>
 */
class EvaluationPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Ganjil 2025/2026',
            'academic_year' => '2025/2026',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonth(),
            'status' => PeriodStatus::Draft,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PeriodStatus::Open]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PeriodStatus::Closed]);
    }

    public function genap(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Genap 2025/2026',
            'semester_type' => SemesterType::Genap,
        ]);
    }
}
