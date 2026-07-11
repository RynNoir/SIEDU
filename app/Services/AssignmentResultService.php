<?php

namespace App\Services;

use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentResultService
{
    /**
     * Agregasi hasil evaluasi satu assignment. Anonim — TIDAK PERNAH student_id.
     *
     * @return array{respondents:int, classSize:int, categoryScores:Collection, overallAvg:float, threshold:int, impressions:Collection}
     */
    public function for(CourseClassAssignment $assignment, ?string $ratingFilter = null): array
    {
        $respondents = $assignment->evaluations()->count();
        $classSize = Student::where('class_group_id', $assignment->class_group_id)->count();

        $categoryScores = EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->join('evaluation_questions', 'evaluation_answers.evaluation_question_id', '=', 'evaluation_questions.id')
            ->where('evaluations.course_class_assignment_id', $assignment->id)
            ->groupBy('evaluation_questions.category')
            ->orderBy('evaluation_questions.category')
            ->selectRaw('evaluation_questions.category, AVG(evaluation_answers.star_rating) as avg_rating')
            ->get();

        $overallAvg = (float) EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->where('evaluations.course_class_assignment_id', $assignment->id)
            ->avg('evaluation_answers.star_rating');

        $threshold = (int) config('evaluation.anonymity_min_respondents');
        $impressions = collect();

        if ($respondents >= $threshold) {
            // Select eksplisit — tanpa student_id (PRD §8).
            $rows = DB::table('evaluation_impressions as i')
                ->join('evaluations as e', 'i.evaluation_id', '=', 'e.id')
                ->where('e.course_class_assignment_id', $assignment->id)
                ->where(fn ($q) => $q->whereNotNull('i.impression_text')->orWhereNotNull('i.suggestion_text'))
                ->selectRaw('i.impression_text, i.suggestion_text, (SELECT AVG(star_rating) FROM evaluation_answers a WHERE a.evaluation_id = e.id) as avg_rating')
                ->get();

            $impressions = collect($rows)->when($ratingFilter, fn ($items) => $items->filter(function ($r) use ($ratingFilter): bool {
                $avg = (float) $r->avg_rating;

                return match ($ratingFilter) {
                    'high' => $avg >= 4,
                    'mid' => $avg >= 3 && $avg < 4,
                    'low' => $avg < 3,
                    default => true,
                };
            }))->values();
        }

        return compact('respondents', 'classSize', 'categoryScores', 'overallAvg', 'threshold', 'impressions');
    }
}
