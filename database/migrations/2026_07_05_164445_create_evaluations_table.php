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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->foreignId('course_class_assignment_id')->constrained();
            $table->foreignId('evaluation_period_id')->constrained();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->unique(
                ['student_id', 'course_class_assignment_id', 'evaluation_period_id'],
                'evaluations_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
