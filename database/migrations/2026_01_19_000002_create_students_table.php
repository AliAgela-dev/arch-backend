<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('student_number')->unique();
            $table->string('name');
            $table->string('nationality');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('restrict');
            $table->foreignId('program_id')->constrained('programs')->onDelete('restrict');
            $table->uuid('drawer_id')->nullable();
            $table->integer('enrollment_year');
            $table->integer('graduation_year')->nullable();
            $table->string('student_status')->default('active');
            $table->string('location_status')->default('in_location');
            $table->timestamps();

            $table->foreign('drawer_id')->references('id')->on('drawers')->onDelete('set null');
            
            $table->index('student_number');
            $table->index('student_status');
            $table->index('location_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
