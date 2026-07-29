<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahun angkatan = tahun mahasiswa masuk kampus (PRD §2.3).
     *
     * Disimpan eksplisit, bukan diturunkan: NIM di sistem ini tidak mengandung
     * angkatan, dan begitu ada mahasiswa cuti, kombinasi kelas + semester tidak
     * lagi mencerminkan angkatan aslinya. Nilainya immutable — promosi semester
     * maupun kelulusan tidak pernah mengubahnya, sehingga tetap jadi penanda
     * kohort yang valid untuk alumni.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->smallInteger('enrollment_year')->unsigned()->nullable()->after('study_program_id');
        });

        $this->backfillEnrollmentYear();

        Schema::table('students', function (Blueprint $table) {
            $table->smallInteger('enrollment_year')->unsigned()->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('enrollment_year');
        });
    }

    /**
     * Isi data lama: tahun awal academic_year kelas dikurangi tingkat yang sudah
     * ditempuh. Tingkat dihitung dari semester MAHASISWA (ceil(semester / 2)),
     * bukan year_level kelas, supaya mahasiswa yang tertinggal setelah cuti tetap
     * memetakan ke angkatan aslinya.
     *
     * Ditulis di PHP (bukan SQL mentah) karena pembagian integer & SUBSTR berbeda
     * perilaku antara MySQL (dev) dan SQLite (test).
     */
    private function backfillEnrollmentYear(): void
    {
        DB::table('students')
            ->join('class_groups', 'class_groups.id', '=', 'students.class_group_id')
            ->select([
                'students.id',
                'students.current_semester',
                'class_groups.academic_year',
            ])
            ->orderBy('students.id')
            ->chunk(500, function ($students): void {
                foreach ($students as $student) {
                    $intakeYear = (int) substr((string) $student->academic_year, 0, 4);
                    $yearsCompleted = (int) ceil($student->current_semester / 2) - 1;

                    DB::table('students')
                        ->where('id', $student->id)
                        ->update(['enrollment_year' => $intakeYear - $yearsCompleted]);
                }
            });
    }
};
