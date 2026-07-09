<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KaprodiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (StudyProgram::all() as $prodi) {
            User::factory()->create([
                'name' => "Kaprodi {$prodi->code}",
                'email' => 'kaprodi.'.Str::lower($prodi->code).'@siedu.test',
                'role' => Role::Kaprodi,
                'study_program_id' => $prodi->id,
                'must_change_password' => true,
            ]);
        }
    }
}
