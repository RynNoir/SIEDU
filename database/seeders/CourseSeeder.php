<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Semester ganjil yang punya kelas di seeder ini: 1 (tahun 1) & 3 (tahun 2)
        foreach (StudyProgram::all() as $prodi) {
            foreach ([1, 3] as $semester) {
                Course::factory()->count(4)->create([
                    'study_program_id' => $prodi->id,
                    'semester' => $semester,
                ]);
            }
        }
    }
}
