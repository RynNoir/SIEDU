<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationPeriodRequest;
use App\Models\EvaluationPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationPeriodController extends Controller
{
    public function index(): View
    {
        $periods = EvaluationPeriod::orderBy('start_date', 'desc')->paginate(20);

        return view('admin.evaluation-periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('admin.evaluation-periods.create');
    }

    public function store(EvaluationPeriodRequest $request): RedirectResponse
    {
        // Periode baru selalu dibuat 'draft'; dibuka lewat aksi terpisah.
        EvaluationPeriod::create([...$request->validated(), 'status' => PeriodStatus::Draft]);

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode ditambahkan (status draft).');
    }

    public function edit(EvaluationPeriod $evaluationPeriod): View
    {
        return view('admin.evaluation-periods.edit', ['period' => $evaluationPeriod]);
    }

    public function update(EvaluationPeriodRequest $request, EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->update($request->validated());

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode diperbarui.');
    }

    public function destroy(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->delete();

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode dihapus.');
    }

    /**
     * Buka periode ini — otomatis menutup periode open lain (periode tunggal, §6.2/§7.7).
     */
    public function open(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->activate();

        return back()->with('success', "Periode \"{$evaluationPeriod->name}\" dibuka. Periode open lain otomatis ditutup.");
    }

    public function close(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->update(['status' => PeriodStatus::Closed]);

        return back()->with('success', "Periode \"{$evaluationPeriod->name}\" ditutup.");
    }
}
