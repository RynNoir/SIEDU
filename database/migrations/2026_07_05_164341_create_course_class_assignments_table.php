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
        Schema::create('course_class_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained();
            $table->foreignId('lecturer_id')->constrained();
            $table->foreignId('class_group_id')->constrained();
            $table->foreignId('evaluation_period_id')->constrained();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(
                ['course_id', 'lecturer_id', 'class_group_id', 'evaluation_period_id'],
                'course_class_assignments_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_class_assignments');
    }
};
