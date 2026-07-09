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
        ]);

        $this->call([
            AdminSeeder::class,
        ]);

        $this->call([
            KaprodiSeeder::class,
        ]);

        $this->call([
            ClassGroupSeeder::class,
        ]);
        $this->call([
            CourseSeeder::class,
        ]);

        $this->call([
            LecturerSeeder::class,
        ]);

        $this->call([
            StudentSeeder::class,
        ]);

        $this->call([
            EvaluationQuestionSeeder::class,
        ]);

        $this->call([
            EvaluationPeriodSeeder::class,
        ]);

        $this->call([
            CourseClassAssignmentSeeder::class,
        ]);
    }
}
