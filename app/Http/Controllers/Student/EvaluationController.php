<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\EvaluationRequest;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->first();

        $assignments = collect();
        $doneIds = [];

        if ($period && $student) {
            $assignments = CourseClassAssignment::with(['course', 'lecturer'])
                ->where('class_group_id', $student->class_group_id)
                ->where('evaluation_period_id', $period->id)
                ->get();

            $doneIds = Evaluation::where('student_id', $student->id)
                ->where('evaluation_period_id', $period->id)
                ->pluck('course_class_assignment_id')
                ->all();
        }

        return view('student.evaluations.index', compact('period', 'assignments', 'doneIds'));
    }

    public function show(CourseClassAssignment $assignment): View|RedirectResponse
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->first();
        abort_if($period === null, 403);
        $this->guardAssignment($assignment, $student, $period->id);

        if ($this->alreadySubmitted($student, $assignment, $period->id)) {
            return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi ini sudah pernah diisi.');
        }

        $assignment->load(['course', 'lecturer']);
        $questions = EvaluationQuestion::active()->get();

        return view('student.evaluations.show', compact('assignment', 'questions'));
    }

    public function store(EvaluationRequest $request, CourseClassAssignment $assignment): RedirectResponse
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->firstOrFail();
        $this->guardAssignment($assignment, $student, $period->id);

        if ($this->alreadySubmitted($student, $assignment, $period->id)) {
            return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi ini sudah pernah dikirim.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $student, $assignment, $period): void {
            $evaluation = $student->evaluations()->create([
                'course_class_assignment_id' => $assignment->id,
                'evaluation_period_id' => $period->id,
                'submitted_at' => now(),
            ]);

            foreach ($data['answers'] as $questionId => $rating) {
                $evaluation->answers()->create([
                    'evaluation_question_id' => (int) $questionId,
                    'star_rating' => (int) $rating,
                ]);
            }

            $evaluation->impression()->create([
                'impression_text' => $data['impression_text'] ?? null,
                'suggestion_text' => $data['suggestion_text'] ?? null,
            ]);
        });

        return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi terkirim. Terima kasih!');
    }

    /**
     * Mahasiswa hanya boleh mengisi assignment kelasnya sendiri di periode open.
     */
    private function guardAssignment(CourseClassAssignment $assignment, ?Student $student, int $periodId): void
    {
        abort_unless(
            $student !== null
            && $assignment->class_group_id === $student->class_group_id
            && $assignment->evaluation_period_id === $periodId,
            403,
        );
    }

    private function alreadySubmitted(Student $student, CourseClassAssignment $assignment, int $periodId): bool
    {
        return $student->evaluations()
            ->where('course_class_assignment_id', $assignment->id)
            ->where('evaluation_period_id', $periodId)
            ->exists();
    }
}
