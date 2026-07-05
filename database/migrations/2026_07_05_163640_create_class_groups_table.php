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
        Schema::create('class_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained();
            $table->string('academic_year', 9);
            $table->tinyInteger('year_level')->unsigned();
            $table->string('class_letter', 1);
            $table->string('class_code', 10);
            $table->integer('capacity')->default(25);
            $table->timestamps();

            $table->unique(['academic_year', 'class_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_groups');
    }
};
