<?php

namespace Database\Factories;

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_class_assignment_id' => CourseClassAssignment::factory(),
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'submitted_at' => now(),
        ];
    }
}
