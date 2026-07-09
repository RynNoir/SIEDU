<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class ClassGroupSeeder extends Seeder
{
    private const ACADEMIC_YEAR = '2025/2026';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Semua prodi: 1 kelas tahun-1 (A)
        foreach (StudyProgram::all() as $prodi) {
            $this->makeClass($prodi, 1, 'A');
        }

        // MI diberi data lebih kaya: kelas paralel (B) + kelas tahun-2 (A)
        $mi = StudyProgram::where('code', 'MI')->firstOrFail();
        $this->makeClass($mi, 1, 'B');
        $this->makeClass($mi, 2, 'A');
    }

    private function makeClass(StudyProgram $prodi, int $yearLevel, string $letter): void
    {
        ClassGroup::create([
            'study_program_id' => $prodi->id,
            'academic_year' => self::ACADEMIC_YEAR,
            'year_level' => $yearLevel,
            'class_letter' => $letter,
            // class_code = {KODE_PRODI}{TAHUN}{HURUF} (PRD §2.2)
            'class_code' => "{$prodi->code}{$yearLevel}{$letter}",
            'capacity' => 25,
        ]);
    }
}
