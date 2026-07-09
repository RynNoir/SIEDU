<?php

namespace Database\Seeders;

use App\Enums\PeriodStatus;
use App\Enums\SemesterType;
use App\Models\EvaluationPeriod;
use Illuminate\Database\Seeder;

class EvaluationPeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Histori (closed) — untuk uji filter perbandingan antar periode.
        EvaluationPeriod::create([
            'name' => 'Ganjil 2024/2025',
            'academic_year' => '2024/2025',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-31',
            'status' => PeriodStatus::Closed,
        ]);

        // Periode aktif (open) — hanya boleh ada 1 open di satu waktu (PRD §7.7).
        EvaluationPeriod::create([
            'name' => 'Ganjil 2025/2026',
            'academic_year' => '2025/2026',
            'semester_type' => SemesterType::Ganjil,
            'start_date' => now()->subWeeks(2),
            'end_date' => now()->addWeeks(6),
            'status' => PeriodStatus::Open,
        ]);
    }
}
