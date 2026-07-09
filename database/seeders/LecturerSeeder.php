<?php

namespace Database\Seeders;

use App\Models\Lecturer;
use App\Models\StudyProgram;
use Illuminate\Database\Seeder;

class LecturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (StudyProgram::all() as $prodi) {
            Lecturer::factory()->count(4)->create([
                'study_program_id' => $prodi->id,
            ]);
        }
    }
}
