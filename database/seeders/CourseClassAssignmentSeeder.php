<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseClassAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $period = EvaluationPeriod::open()->firstOrFail();
        $admin = User::where('role', Role::Admin)->firstOrFail();

        foreach (ClassGroup::all() as $class) {
            $semester = $class->year_level * 2 - 1; // ganjil

            $courses = Course::where('study_program_id', $class->study_program_id)
                ->where('semester', $semester)
                ->get();

            $lecturers = Lecturer::where('study_program_id', $class->study_program_id)
                ->get()
                ->values();

            if ($courses->isEmpty() || $lecturers->isEmpty()) {
                continue;
            }

            foreach ($courses as $i => $course) {
                $lecturer = $lecturers[$i % $lecturers->count()];

                $this->assign($course->id, $lecturer->id, $class->id, $period->id, $admin->id);

                // Team teaching (PRD §4.4 v1.1): MK pertama di MI1A diampu 2 dosen.
                if ($class->class_code === 'MI1A' && $i === 0) {
                    $second = $lecturers->firstWhere('id', '!=', $lecturer->id);
                    if ($second) {
                        $this->assign($course->id, $second->id, $class->id, $period->id, $admin->id);
                    }
                }
            }
        }
    }

    private function assign(int $courseId, int $lecturerId, int $classId, int $periodId, int $adminId): void
    {
        CourseClassAssignment::create([
            'course_id' => $courseId,
            'lecturer_id' => $lecturerId,
            'class_group_id' => $classId,
            'evaluation_period_id' => $periodId,
            'created_by' => $adminId,
        ]);
    }
}
