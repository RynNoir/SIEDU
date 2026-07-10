<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationQuestionRequest;
use App\Models\EvaluationQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationQuestionController extends Controller
{
    public function index(): View
    {
        $questions = EvaluationQuestion::orderBy('order_number')->paginate(20);

        return view('admin.evaluation-questions.index', compact('questions'));
    }

    public function create(): View
    {
        return view('admin.evaluation-questions.create');
    }

    public function store(EvaluationQuestionRequest $request): RedirectResponse
    {
        EvaluationQuestion::create($request->validated());

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan ditambahkan.');
    }

    public function edit(EvaluationQuestion $evaluationQuestion): View
    {
        return view('admin.evaluation-questions.edit', ['question' => $evaluationQuestion]);
    }

    public function update(EvaluationQuestionRequest $request, EvaluationQuestion $evaluationQuestion): RedirectResponse
    {
        $evaluationQuestion->update($request->validated());

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan diperbarui.');
    }

    public function destroy(EvaluationQuestion $evaluationQuestion): RedirectResponse
    {
        $evaluationQuestion->delete();

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan dihapus.');
    }
}
