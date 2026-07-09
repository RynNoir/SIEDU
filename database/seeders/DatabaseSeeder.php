<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            StudyProgramSeeder::class,
            AdminSeeder::class,
            KaprodiSeeder::class,
            ClassGroupSeeder::class,
            CourseSeeder::class,
            LecturerSeeder::class,
            StudentSeeder::class,
            EvaluationQuestionSeeder::class,
            EvaluationPeriodSeeder::class,
            CourseClassAssignmentSeeder::class,
            EvaluationSeeder::class,
        ]);
    }
}
