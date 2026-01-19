<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('document_type_id')->constrained('document_types')->onDelete('restrict');
            $table->string('file_number')->unique();
            $table->string('file_status')->default('draft');
            $table->string('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
