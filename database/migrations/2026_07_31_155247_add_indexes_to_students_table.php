<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index untuk kolom yang difilter di hampir setiap pembukaan halaman.
     *
     * Sejak scope Student::enrolled()/alumni() dipakai, SETIAP pembukaan daftar
     * mahasiswa memfilter `status`, dan halaman Alumni memfilter `status` +
     * `enrollment_year`. Keduanya belum punya index, jadi biayanya tumbuh linear
     * tiap angkatan lulus dan menumpuk.
     *
     * Index gabungan (status, enrollment_year) melayani kedua pola sekaligus:
     * filter `status` saja memakai kolom terkiri, dan halaman Alumni yang
     * menyaring keduanya memakai index penuh.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index(['status', 'enrollment_year']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status', 'enrollment_year']);
        });
    }
};
