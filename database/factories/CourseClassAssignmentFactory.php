<?php

namespace Database\Factories;

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseClassAssignment>
 */
class CourseClassAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'lecturer_id' => Lecturer::factory(),
            'class_group_id' => ClassGroup::factory(),
            'evaluation_period_id' => EvaluationPeriod::factory(),
            'created_by' => User::factory()->admin(),
        ];
    }
}
