<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_refinements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_document_id')
                ->unique()
                ->constrained('student_documents')
                ->onDelete('cascade');
            $table->jsonb('structured_data')->nullable();
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->text('raw_response')->nullable();
            $table->string('refinement_status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('refinement_status');
            $table->index('confidence_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_refinements');
    }
};
