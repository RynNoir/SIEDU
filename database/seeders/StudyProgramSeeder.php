<?php

namespace Database\Seeders;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class StudyProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['name' => 'Manajemen Informatika', 'code' => 'MI', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Teknik Komputer', 'code' => 'TK', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Sistem Informasi', 'code' => 'SI', 'degree_level' => DegreeLevel::D3, 'total_semesters' => 6],
            ['name' => 'Teknologi Rekayasa Perangkat Lunak', 'code' => 'TRPL', 'degree_level' => DegreeLevel::D4, 'total_semesters' => 8],
            ['name' => 'Animasi', 'code' => 'ANIM', 'degree_level' => DegreeLevel::D4, 'total_semesters' => 8],
        ];

        foreach ($programs as $program) {
            StudyProgram::create($program);
        }
    }
}
