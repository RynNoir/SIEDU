<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('nim')->unique();
            $table->string('name');
            $table->foreignId('study_program_id')->constrained();
            $table->foreignId('class_group_id')->constrained();
            $table->tinyInteger('current_semester')->unsigned();
            $table->enum('status', ['aktif', 'cuti', 'DO', 'lulus'])->default('aktif');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
