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
        Schema::create('evaluation_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('academic_year', 9);
            $table->enum('semester_type', ['ganjil', 'genap']);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'open', 'closed'])->default('draft')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_periods');
    }
};
