<?php

namespace App\Models;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use Database\Factories\EvaluationPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'academic_year', 'semester_type', 'start_date', 'end_date', 'status'])]
class EvaluationPeriod extends Model
{
    /** @use HasFactory<EvaluationPeriodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'semester_type' => SemesterType::class,
            'status' => PeriodStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Scope periode yang sedang dibuka: EvaluationPeriod::open()->first().
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', PeriodStatus::Open);
    }

    /**
     * Buka periode ini & tutup periode open lain — periode tunggal
     * (PRD §6.2/§7.7). Dipanggil dari controller/service di Fase 5.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            static::query()
                ->where('status', PeriodStatus::Open)
                ->whereKeyNot($this->getKey())
                ->update(['status' => PeriodStatus::Closed]);

            $this->update(['status' => PeriodStatus::Open]);
        });
    }

    /**
     * @return HasMany<CourseClassAssignment, $this>
     */
    public function courseClassAssignments(): HasMany
    {
        return $this->hasMany(CourseClassAssignment::class);
    }

    /**
     * @return HasMany<Evaluation, $this>
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
