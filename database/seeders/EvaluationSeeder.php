<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = EvaluationQuestion::active()->get();

        $mi1a = ClassGroup::where('class_code', 'MI1A')->firstOrFail();

        // Ambil 6 mahasiswa (> threshold 5) supaya kesan & saran tampil di dashboard.
        $students = Student::where('class_group_id', $mi1a->id)->take(6)->get();

        $assignments = CourseClassAssignment::where('class_group_id', $mi1a->id)->get();

        foreach ($assignments as $assignment) {
            foreach ($students as $student) {
                $evaluation = Evaluation::create([
                    'student_id' => $student->id,
                    'course_class_assignment_id' => $assignment->id,
                    'evaluation_period_id' => $assignment->evaluation_period_id,
                    'submitted_at' => now(),
                ]);

                foreach ($questions as $question) {
                    EvaluationAnswer::create([
                        'evaluation_id' => $evaluation->id,
                        'evaluation_question_id' => $question->id,
                        'star_rating' => rand(3, 5),
                    ]);
                }

                EvaluationImpression::create([
                    'evaluation_id' => $evaluation->id,
                    'impression_text' => 'Penjelasan dosen mudah dipahami dan runtut.',
                    'suggestion_text' => 'Tugas bisa diberi contoh pengerjaan lebih banyak.',
                ]);
            }
        }
    }
}
