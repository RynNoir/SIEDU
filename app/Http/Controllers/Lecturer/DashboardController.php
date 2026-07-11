<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationPeriod;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = auth()->user()->lecturer;
        $selectedPeriodId = $request->input('period_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'classGroup', 'evaluationPeriod'])
            ->where('lecturer_id', $lecturer->id)
            ->when($selectedPeriodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->withCount('evaluations')
            ->orderByDesc('evaluation_period_id')
            ->get();

        return view('lecturer.dashboard', [
            'assignments' => $assignments,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'selectedPeriodId' => $selectedPeriodId,
        ]);
    }

    public function show(Request $request, CourseClassAssignment $assignment): View
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'classGroup', 'evaluationPeriod']);

        $respondents = $assignment->evaluations()->count();
        $classSize = Student::where('class_group_id', $assignment->class_group_id)->count();

        // Rata-rata per kategori (agregasi answers join questions).
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
        $ratingFilter = $request->input('rating'); // null | high | mid | low
        $impressions = collect();

        if ($respondents >= $threshold) {
            // PENTING: select eksplisit — TIDAK PERNAH student_id.
            $rows = DB::table('evaluation_impressions as i')
                ->join('evaluations as e', 'i.evaluation_id', '=', 'e.id')
                ->where('e.course_class_assignment_id', $assignment->id)
                ->where(fn ($q) => $q->whereNotNull('i.impression_text')->orWhereNotNull('i.suggestion_text'))
                ->selectRaw('i.impression_text, i.suggestion_text, (SELECT AVG(star_rating) FROM evaluation_answers a WHERE a.evaluation_id = e.id) as avg_rating')
                ->get();

            $impressions = collect($rows)->when($ratingFilter, function ($items) use ($ratingFilter) {
                return $items->filter(function ($r) use ($ratingFilter): bool {
                    $avg = (float) $r->avg_rating;

                    return match ($ratingFilter) {
                        'high' => $avg >= 4,
                        'mid' => $avg >= 3 && $avg < 4,
                        'low' => $avg < 3,
                        default => true,
                    };
                });
            })->values();
        }

        return view('lecturer.assignments.show', [
            'assignment' => $assignment,
            'respondents' => $respondents,
            'classSize' => $classSize,
            'categoryScores' => $categoryScores,
            'overallAvg' => $overallAvg,
            'threshold' => $threshold,
            'impressions' => $impressions,
            'ratingFilter' => $ratingFilter,
        ]);
    }
}
